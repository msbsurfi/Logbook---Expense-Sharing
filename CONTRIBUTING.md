# Contributing to Logbook

Thank you for your interest in contributing to Logbook! Contributions of all kinds are welcome — bug reports, feature requests, documentation improvements, and code changes.

---

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Reporting Bugs](#reporting-bugs)
- [Requesting Features](#requesting-features)
- [Submitting Code Changes](#submitting-code-changes)
- [Coding Standards](#coding-standards)
- [Commit Message Format](#commit-message-format)

---

## Code of Conduct

All participants are expected to maintain a respectful, professional environment. Harassment, discrimination, or disrespectful communication of any kind will not be tolerated.

---

## Reporting Bugs

If you discover a bug, please [open an issue](https://github.com/msbsurfi/Logbook---Expense-Sharing/issues) and include:

1. A clear, descriptive title.
2. Steps to reproduce the bug.
3. Expected behavior vs. actual behavior.
4. Your environment: PHP version, MySQL version, web server, OS.
5. Relevant error messages or screenshots.

> **Security vulnerabilities** should **not** be reported publicly. Please open a [GitHub Security Advisory](https://github.com/msbsurfi/Logbook---Expense-Sharing/security/advisories/new) or contact the maintainer directly.

---

## Requesting Features

Feature requests are welcome. Open an issue with the label `enhancement` and describe:

1. The problem you are trying to solve.
2. Your proposed solution or feature description.
3. Any alternatives you considered.

---

## Submitting Code Changes

1. **Fork** the repository and create a descriptive branch:
   ```bash
   git checkout -b fix/issue-123-transaction-balance
   ```
2. Make your changes following the [Coding Standards](#coding-standards) below.
3. Test your changes thoroughly.
4. Commit using the [Commit Message Format](#commit-message-format).
5. Open a **Pull Request** targeting the `main` branch.
6. Reference the related issue in the PR description.

---

## Coding Standards

- **PHP 8.0+** syntax only. Use typed properties, named arguments, and match expressions where appropriate.
- Follow the **PSR-12** coding style (4-space indentation, braces on same line for control structures).
- Namespace all new classes under the `App\` namespace and place them in the appropriate `app/` subdirectory.
- All database queries **must** use PDO prepared statements — never interpolate user input into SQL.
- Validate CSRF tokens on every state-changing POST request using `Security::validateCsrf()`.
- Keep controller methods short; push logic into model methods.
- Do **not** commit credentials, `.env` files, or `config/database.php` / `config/mail.php` with real values.

---

## Commit Message Format

Use the following format for commit messages:

```
<type>(<scope>): <short description>

[optional body]
```

**Types:** `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

**Examples:**

```
feat(transactions): add custom split support for expenses
fix(auth): correct rate-limit check in resend verification
docs: update README installation steps
```

