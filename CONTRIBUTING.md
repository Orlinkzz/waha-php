# Contributing Guide for WAHA PHP Client

Thank you for your interest in contributing to this project! 🎉

## 📋 Table of Contents

* [How to Contribute](#how-to-contribute)
* [Reporting Bugs](#reporting-bugs)
* [Suggesting New Features](#suggesting-new-features)
* [Pull Requests](#pull-requests)
* [Development Setup](#development-setup)
* [Coding Standards](#coding-standards)
* [Testing](#testing)
* [Commit Guidelines](#commit-guidelines)

## 🚀 How to Contribute

There are several ways you can contribute:

1. **Report Bugs** – Help us identify and fix issues.
2. **Suggest Features** – Recommend improvements or new functionality.
3. **Submit Pull Requests** – Contribute code directly.
4. **Improve Documentation** – Enhance or expand documentation.
5. **Testing** – Help test and review code.

## 🐛 Reporting Bugs

Before reporting a bug, please make sure:

* ✅ Check existing issues to avoid duplicates.
* ✅ Use the available issue template.
* ✅ Include detailed information:

  * PHP version
  * Package version (`composer show orlinkzz/waha-php`)
  * Steps to reproduce the issue
  * Expected behavior vs actual behavior
  * Error messages or stack traces (if available)

### Bug Report Template

```markdown
**Bug Description**
A clear and concise description of the bug.

**Steps to Reproduce**
1. Set up environment '...'
2. Run code '...'
3. Observe the error

**Expected Behavior**
Describe what should happen.

**Actual Behavior**
Describe what actually happens.

**Environment**
- PHP Version: [e.g. 8.1.0]
- Package Version: [e.g. 1.0.0]
- OS: [e.g. Ubuntu 20.04]

**Additional Context**
Any other relevant information.
```

## 💡 Suggesting New Features

To suggest a new feature:

1. Open a GitHub Issue.
2. Use the `enhancement` label.
3. Clearly explain:

   * The problem you are trying to solve.
   * The proposed solution.
   * Alternative solutions you have considered.
   * Use cases and examples.

## 🔀 Pull Requests

### Before Creating a Pull Request

1. **Fork the repository** and clone it locally.

2. **Create a new branch** from `main`:

   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/bug-description
   ```

3. **Commit your changes** with a clear message.

4. **Push your branch**:

   ```bash
   git push origin feature/your-feature-name
   ```

5. **Open a Pull Request** targeting the `main` branch.

### Branch Naming Convention

* `feature/feature-name` – New features
* `fix/bug-description` – Bug fixes
* `docs/documentation-update` – Documentation changes
* `refactor/refactor-name` – Refactoring work
* `test/test-name` – Testing-related changes

### Pull Request Checklist

Before submitting a PR, ensure:

* [ ] Code follows the project's coding standards.
* [ ] All tests pass (`composer test`).
* [ ] No syntax errors exist.
* [ ] Documentation has been updated if necessary.
* [ ] Commit messages are clear and descriptive.
* [ ] Your branch is up-to-date with `main`.
* [ ] Changes have been tested locally.

### Pull Request Template

```markdown
## Description
Describe the changes you made.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation
- [ ] Refactoring
- [ ] Testing

## Related Issues
Closes #(issue number)

## Testing
Describe how you tested your changes.

## Screenshots (if applicable)
Add screenshots for UI-related changes.

## Checklist
- [ ] My code follows the code style of this project
- [ ] My change requires documentation updates
- [ ] I have updated the documentation accordingly
- [ ] I have added tests covering my changes
- [ ] All new and existing tests pass
```

## 💻 Development Setup

### Prerequisites

* PHP 8.1 or higher
* Composer
* Git
* Running WAHA instance (for testing)

### Local Setup

1. **Fork and clone the repository:**

   ```bash
   git clone https://github.com/your-username/waha-php.git
   cd waha-php
   ```

2. **Install dependencies:**

   ```bash
   composer install
   ```

3. **Copy the environment file:**

   ```bash
   cp .env.example .env
   ```

4. **Set up a WAHA instance:**

   * Install and run WAHA.
   * Update your `.env` configuration accordingly.

5. **Run tests:**

   ```bash
   composer test
   ```

### Project Structure

```text
waha-php/
├── src/
│   ├── Client/
│   ├── Database/
│   ├── Exceptions/
│   ├── Laravel/
│   ├── Message/
│   └── Queue/
├── tests/
├── database/
├── config/
└── docs/
```

## 📝 Coding Standards

### PHP Standards

This project follows the PSR-12 coding standard:

* **Indentation:** 4 spaces (no tabs)
* **Line Length:** Maximum 120 characters
* **Braces:** Opening braces on the same line as declarations
* **Visibility:** Always declare visibility (`public`, `protected`, `private`)
* **Type Hints:** Use parameter and return type declarations whenever possible

### Code Style Example

```php
<?php

namespace Orlinkzz\Waha;

use Orlinkzz\Waha\Client\WahaHttpClient;

class WahaClient
{
    private WahaHttpClient $http;

    public function __construct(
        private readonly WahaConfig $config
    ) {
        $this->http = new WahaHttpClient($config);
    }

    public function sendText(
        string $chatId,
        string $text,
        ?string $session = null
    ): array {
        // Implementation
    }
}
```

### Naming Conventions

* **Classes:** PascalCase (`WahaClient`, `OutgoingMessage`)
* **Methods:** camelCase (`sendText`, `getMessageLogs`)
* **Variables:** camelCase (`$chatId`, `$sessionId`)
* **Constants:** UPPER_SNAKE_CASE (`MAX_RETRY_COUNT`)
* **Database Tables:** snake_case (`waha_sessions`, `waha_messages`)

### Documentation

* Use PHPDoc for all public methods.
* Include parameter and return types.
* Add usage examples for complex functionality.

```php
/**
 * Send a text message using the anti-ban flow.
 *
 * @param string $chatId WhatsApp chat ID (e.g. '628123456789@c.us')
 * @param string $text Message content
 * @param string|null $session Session name
 *
 * @return array Response from the WAHA API
 *
 * @throws WahaException If the API request fails
 *
 * @example
 * $client->sendText('628123456789@c.us', 'Hello!');
 */
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run a specific test file
vendor/bin/phpunit tests/DatabaseTest.php
```

### Writing Tests

* Every new feature should include tests.
* Every bug fix should include a regression test.
* Use descriptive test names.
* Tests should be independent from each other.

### Example Test

```php
public function test_send_text_message_with_anti_banned_flow(): void
{
    $client = new WahaClient($this->config);

    $result = $client->sendText(
        '628123456789@c.us',
        'Test message'
    );

    $this->assertIsArray($result);
    $this->assertArrayHasKey('id', $result);
}
```

### Test Coverage

* Aim for at least 80% code coverage.
* Focus on critical paths and edge cases.
* Test error handling and exception scenarios.

## 📦 Commit Guidelines

### Commit Message Format

Follow the Conventional Commits specification:

```text
<type>(<scope>): <subject>

<body>

<footer>
```

### Commit Types

* `feat` – New feature
* `fix` – Bug fix
* `docs` – Documentation only
* `style` – Formatting changes only
* `refactor` – Code refactoring
* `test` – Test additions or updates
* `chore` – Maintenance and tooling updates

### Examples

```bash
feat(client): add support for voice messages

fix(database): resolve connection timeout issue

docs(readme): add Laravel installation guide

refactor(client): simplify anti-ban flow

test(database): add session repository tests
```

### Best Practices

* Use imperative mood ("add" instead of "added").
* Keep the subject line under 50 characters when possible.
* Use the body section for detailed explanations.
* Reference issues in the footer (`Closes #123`, `Fixes #456`).

## 👥 Code Review Process

### For Contributors

1. Submit a pull request with a clear description.
2. Wait for maintainer review.
3. Address review feedback if requested.
4. Push updates to the same branch.
5. Wait for approval from at least one maintainer.

### For Reviewers

* Be respectful and constructive.
* Focus on code quality rather than personal preference.
* Explain the reasoning behind suggestions.
* Approve when standards are met.

## 🤝 Community Guidelines

### Code of Conduct

* **Be respectful** – Respect all contributors.
* **Be constructive** – Provide helpful feedback.
* **Be inclusive** – Welcome contributors from all backgrounds.
* **Be patient** – Everyone is learning.
* **Be collaborative** – Work together toward better outcomes.

### Communication

* Use polite and professional language.
* Avoid spam and excessive self-promotion.
* Stay on topic in issues and pull requests.
* Feel free to use emojis to keep communication friendly 😊

## 📞 Need Help?

If you need assistance:

* Read the project documentation.
* Check existing issues.
* Open a discussion.
* Contact the maintainer: @orlinkzz

## 🎉 Recognition

All contributors will be recognized through:

* Contributors list
* Release notes (for significant contributions)
* Project acknowledgements

---

**Thank you for contributing!** 🙏

Every contribution, no matter how small, helps make this project better.
