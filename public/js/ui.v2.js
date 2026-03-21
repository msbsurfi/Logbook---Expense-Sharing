(function (window, document) {
  'use strict';

  var themeKey = 'logbook_theme';
  var root = document.documentElement;
  var body = document.body;

  function applyTheme(theme) {
    if (theme === 'dark') {
      root.setAttribute('data-theme', 'dark');
    } else {
      root.removeAttribute('data-theme');
    }
    updateThemeButtons();
  }

  function getSavedTheme() {
    try {
      var savedTheme = window.localStorage.getItem(themeKey);
      if (savedTheme) {
        return savedTheme;
      }
    } catch (error) {}

    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
      ? 'dark'
      : 'light';
  }

  function toggleTheme() {
    var isDark = root.getAttribute('data-theme') === 'dark';
    var nextTheme = isDark ? 'light' : 'dark';
    try {
      window.localStorage.setItem(themeKey, nextTheme);
    } catch (error) {}
    applyTheme(nextTheme);
  }

  function updateThemeButtons() {
    var isDark = root.getAttribute('data-theme') === 'dark';
    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      var icon = button.querySelector('i');
      if (icon) {
        icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
      }
    });
  }

  function ensureToastRoot() {
    var existing = document.getElementById('toast-stack');
    if (existing) {
      return existing;
    }

    var stack = document.createElement('div');
    stack.className = 'toast-stack';
    stack.id = 'toast-stack';
    document.body.appendChild(stack);
    return stack;
  }

  function showToast(title, message, type, ttl) {
    var stack = ensureToastRoot();
    var toast = document.createElement('article');
    var safeType = type || 'info';
    toast.className = 'toast ' + safeType;
    toast.innerHTML =
      '<div class="toast-header">' +
        '<strong>' + escapeHtml(title || 'Notice') + '</strong>' +
        '<button type="button" class="toast-close" aria-label="Dismiss notification">' +
          '<i class="fa-solid fa-xmark"></i>' +
        '</button>' +
      '</div>' +
      '<p>' + escapeHtml(message || '') + '</p>';

    var removeToast = function () {
      toast.remove();
    };

    var closeButton = toast.querySelector('.toast-close');
    if (closeButton) {
      closeButton.addEventListener('click', removeToast);
    }

    stack.appendChild(toast);
    window.setTimeout(removeToast, ttl || 5000);
  }

  function showGlobalLoading(visible) {
    var overlay = document.getElementById('global-loading-overlay');
    if (!overlay) {
      return;
    }

    var isVisible = visible !== false;
    overlay.hidden = !isVisible;
  }

  function initTheme() {
    applyTheme(getSavedTheme());
    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      button.addEventListener('click', toggleTheme);
    });
  }

  function initProfileDropdown() {
    var trigger = document.getElementById('profile-menu-button');
    var dropdown = document.getElementById('profile-dropdown');

    if (!trigger || !dropdown) {
      return;
    }

    var closeDropdown = function () {
      dropdown.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
    };

    trigger.addEventListener('click', function (event) {
      event.stopPropagation();
      var isOpen = !dropdown.hidden;
      dropdown.hidden = isOpen;
      trigger.setAttribute('aria-expanded', String(!isOpen));
    });

    document.addEventListener('click', function (event) {
      if (!dropdown.hidden && !dropdown.contains(event.target) && event.target !== trigger) {
        closeDropdown();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeDropdown();
      }
    });
  }

  function initMobileNav() {
    var nav = document.getElementById('mobile-nav');
    var overlay = document.getElementById('mobile-nav-overlay');
    var openButton = document.getElementById('mobile-menu-btn');
    var closeButton = document.getElementById('mobile-nav-close');

    if (!nav || !overlay || !openButton || !closeButton) {
      return;
    }

    var openNav = function () {
      nav.classList.add('is-open');
      nav.setAttribute('aria-hidden', 'false');
      overlay.hidden = false;
      openButton.setAttribute('aria-expanded', 'true');
    };

    var closeNav = function () {
      nav.classList.remove('is-open');
      nav.setAttribute('aria-hidden', 'true');
      overlay.hidden = true;
      openButton.setAttribute('aria-expanded', 'false');
    };

    openButton.addEventListener('click', openNav);
    closeButton.addEventListener('click', closeNav);
    overlay.addEventListener('click', closeNav);
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeNav();
      }
    });
  }

  function initDisableOnSubmit() {
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      var submitButton = form.querySelector('.disable-on-click');
      if (!submitButton || submitButton.disabled) {
        return;
      }

      window.setTimeout(function () {
        if (submitButton.disabled) {
          return;
        }

        var currentLabel = submitButton.dataset.originalLabel || submitButton.textContent.trim() || 'Working';
        submitButton.dataset.originalLabel = currentLabel;
        submitButton.disabled = true;
        submitButton.innerHTML =
          '<span class="btn-spinner"></span>' +
          '<span>' + escapeHtml(submitButton.dataset.loadingText || currentLabel) + '</span>';
      }, 10);
    }, true);
  }

  function initNotifications() {
    var panel = document.getElementById('notifications-panel');
    var list = document.getElementById('notifications-list');
    var trigger = document.getElementById('notifications-btn');
    var countBadge = document.getElementById('notifications-count');
    var closeButton = document.getElementById('notifications-close');
    var scrim = document.getElementById('notifications-scrim');
    var csrfToken = body ? body.dataset.csrfToken : '';

    if (!panel || !list || !trigger || !countBadge || !closeButton || !scrim) {
      return;
    }

    var panelOpen = false;

    var setUnreadCount = function (count) {
      countBadge.textContent = String(count);
      countBadge.classList.toggle('is-hidden', !count);
    };

    var closePanel = function () {
      panel.hidden = true;
      scrim.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      panelOpen = false;
    };

    var openPanel = function () {
      panel.hidden = false;
      scrim.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      panelOpen = true;
    };

    var renderNotifications = function (items) {
      if (!items || !items.length) {
        list.innerHTML =
          '<div class="notifications-empty">' +
            '<i class="fa-regular fa-bell-slash"></i>' +
            '<p>No notifications yet.</p>' +
          '</div>';
        return;
      }

      list.innerHTML = items.map(function (item) {
        var unreadClass = item.is_read ? '' : ' is-unread';
        var action = item.is_read
          ? '<span>Read</span>'
          : '<button type="button" class="notification-mark" data-mark-read="' + String(item.id) + '">Mark read</button>';

        return (
          '<article class="notification-item' + unreadClass + '" data-notification-id="' + String(item.id) + '">' +
            '<div class="notification-topline">' +
              '<div>' +
                '<strong>' + escapeHtml(item.title || 'Notification') + '</strong>' +
                '<span>' + escapeHtml(formatRelativeDate(item.created_at)) + '</span>' +
              '</div>' +
            '</div>' +
            '<div class="notification-body">' + escapeHtml(item.message || '') + '</div>' +
            '<div class="notification-footer">' +
              action +
            '</div>' +
          '</article>'
        );
      }).join('');
    };

    var fetchUnreadCount = function () {
      return window.fetch('/notifications/unread-count', {
        headers: { Accept: 'application/json' }
      })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (payload) {
          setUnreadCount(payload && payload.unread ? payload.unread : 0);
        })
        .catch(function () {});
    };

    var fetchNotifications = function () {
      list.innerHTML =
        '<div class="notifications-empty">' +
          '<i class="fa-solid fa-spinner fa-spin"></i>' +
          '<p>Loading notifications...</p>' +
        '</div>';

      return window.fetch('/notifications/list', {
        headers: { Accept: 'application/json' }
      })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (payload) {
          renderNotifications(payload && payload.notifications ? payload.notifications : []);
        })
        .catch(function () {
          list.innerHTML =
            '<div class="notifications-empty">' +
              '<i class="fa-regular fa-circle-xmark"></i>' +
              '<p>Unable to load notifications.</p>' +
            '</div>';
        });
    };

    var markRead = function (id, itemElement) {
      var bodyPayload = new URLSearchParams();
      bodyPayload.set('id', String(id));
      bodyPayload.set('csrf_token', csrfToken || '');

      return window.fetch('/notifications/mark-read', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          Accept: 'application/json'
        },
        body: bodyPayload.toString()
      })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (payload) {
          if (!payload || payload.status !== 'ok') {
            throw new Error('Unable to update notification');
          }

          if (itemElement) {
            itemElement.classList.remove('is-unread');
            var footer = itemElement.querySelector('.notification-footer');
            if (footer) {
              footer.innerHTML = '<span>Read</span>';
            }
          }
          fetchUnreadCount();
        })
        .catch(function () {
          showToast('Error', 'Unable to mark the notification as read.', 'error');
        });
    };

    trigger.addEventListener('click', function () {
      if (panelOpen) {
        closePanel();
        return;
      }

      openPanel();
      fetchNotifications();
      fetchUnreadCount();
    });

    closeButton.addEventListener('click', closePanel);
    scrim.addEventListener('click', closePanel);

    list.addEventListener('click', function (event) {
      var button = event.target.closest('[data-mark-read]');
      if (!button) {
        return;
      }

      var itemId = button.getAttribute('data-mark-read');
      var itemElement = button.closest('.notification-item');
      if (itemId) {
        markRead(itemId, itemElement);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closePanel();
      }
    });

    fetchUnreadCount();
    window.setInterval(fetchUnreadCount, 30000);
  }

  function formatRelativeDate(dateString) {
    if (!dateString) {
      return 'Unknown time';
    }

    var date = new Date(dateString.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
      return dateString;
    }

    var diffMinutes = Math.round((Date.now() - date.getTime()) / 60000);
    if (diffMinutes < 1) {
      return 'Just now';
    }
    if (diffMinutes < 60) {
      return diffMinutes + ' min ago';
    }

    var diffHours = Math.round(diffMinutes / 60);
    if (diffHours < 24) {
      return diffHours + ' hr ago';
    }

    var diffDays = Math.round(diffHours / 24);
    if (diffDays < 7) {
      return diffDays + ' day' + (diffDays === 1 ? '' : 's') + ' ago';
    }

    return date.toLocaleDateString(undefined, {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&
  }

  document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initProfileDropdown();
    initMobileNav();
    initDisableOnSubmit();
    initNotifications();

    (window.__LOGBOOK_SERVER_TOASTS || []).forEach(function (toast) {
      showToast(toast.title, toast.message, toast.type);
    });
  });

  window.LogbookUI = window.HalkhataUI = {
    applyTheme: applyTheme,
    toggleTheme: toggleTheme,
    showToast: showToast,
    showGlobalLoading: showGlobalLoading
  };
})(window, document);
