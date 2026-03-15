# Contributing to Logbook

Thank you for your interest in Logbook. Because this is **proprietary software** owned by MD Shifat Bin Siddique Urfi, contributions are handled differently from typical open-source projects. Please read the following guidelines carefully before submitting anything.

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

If you discover a bug, please open a [GitHub Issue](https://github.com/msbsurfi/Logbook/issues) and include:

1. A clear, descriptive title.
2. Steps to reproduce the bug.
3. Expected behavior vs. actual behavior.
4. Your environment: PHP version, MySQL version, web server, OS.
5. Relevant error messages or screenshots.

> **Security vulnerabilities** must **not** be reported publicly. Please contact the owner directly via the information in the [LICENSE](LICENSE) file.

---

## Requesting Features

Feature requests are welcome. Open an issue with the label `enhancement` and describe:

1. The problem you are trying to solve.
2. Your proposed solution or feature description.
3. Any alternatives you considered.

---

## Submitting Code Changes

Because the Software is proprietary, all contributions require **explicit written permission** from the owner. Before starting work on any change:

1. Open an issue describing the change you wish to make.
2. Wait for approval from the repository owner.
3. Only proceed with implementation once you have written permission.

If approved:

1. **Fork** the repository and create a descriptive branch:
   ```bash
   git checkout -b fix/issue-123-transaction-balance
   ```
2. Make your changes following the [Coding Standards](#coding-standards) below.
3. Test your changes thoroughly.
4. Commit using the [Commit Message Format](#commit-message-format).
5. Open a **Pull Request** targeting the `main` branch.
6. Reference the related issue in the PR description.

By submitting a pull request, you agree to assign all rights to your contribution to MD Shifat Bin Siddique Urfi.

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

---

*Logbook is copyright © 2024 MD Shifat Bin Siddique Urfi. All rights reserved.*
