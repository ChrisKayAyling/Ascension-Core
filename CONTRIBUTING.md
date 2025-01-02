# Contributing to Ascension-Core
🎉 Thank you for considering contributing to the Ascension-Core project! We welcome contributions from everyone, whether you're fixing a bug, proposing a feature, or improving the documentation.

Please take a moment to review these guidelines before submitting your contribution.

# 📋 Table of Contents
1. Getting Started
2. Code of Conduct
3. How to Contribute
4. Reporting Bugs
5. Suggesting Features
6. Submitting Code Changes
7. Development Setup
8. Coding Standards
9. Pull Request Guidelines
10. License

# 🛠 Getting Started
To get started with the project:

Fork the repository: Click the "Fork" button on the top right of this page.
Clone your fork: Use git clone to clone your fork locally.

```
git clone https://github.com/your-username/php-routing-core.git
```

Install dependencies: Use Composer to install project dependencies.
```
composer install
```
Create a new branch: For your work, create a new branch from the main branch.
```
git checkout -b your-feature-branch
```
# 🤝 Code of Conduct
We follow a Code of Conduct to foster an inclusive and welcoming environment. Please read it before contributing.

# 🚀 How to Contribute
Reporting Bugs
If you find a bug, please open an issue and include:

A clear and descriptive title.
A detailed description of the problem.
Steps to reproduce the issue.
Any relevant code snippets or error messages.
Suggesting Features
We welcome suggestions! To propose a new feature, please open an issue and provide:

A detailed explanation of the feature request.
Why this feature would be useful.
Any alternative solutions you have considered.
Submitting Code Changes
To submit a code change:

Make sure your branch is up-to-date with the main branch.
```
git checkout main

git pull origin main
```
Make your changes and commit them with a meaningful message.

```
git add .

git commit -m "Describe your changes clearly"
```
Push your changes to your fork and create a pull request.

```
git push origin your-feature-branch
```
Open a pull request on GitHub and describe your changes in detail.

# 🖥 Development Setup
Ensure you have the following prerequisites:

PHP 8.0 or higher
Composer
Running Tests
The project uses PHPUnit for testing. Run the tests with:

```
composer test
```
Ensure all tests pass before submitting your changes.

Linting
We use PHP CodeSniffer for code style checks. Run linting with:

```
composer lint
```

Fix any linting errors before submitting your code.

# ✨ Coding Standards
Please follow the PSR-12 coding standard. Here are a few key points:

Use spaces for indentation (4 spaces).
Use camelCase for variable and method names.
Use snake_case for file names.
Use PascalCase for class names.
Place opening braces on the same line as the declaration.
You can automatically format your code using:

```
composer format
```

# ✅ Pull Request Guidelines
Ensure your code follows the project's coding standards.
Add tests for your changes if applicable.
Ensure that all tests pass (composer test).
Write a clear and descriptive pull request title and message.
Link any related issues in the pull request.
Your pull request will be reviewed by a maintainer, who may ask for changes before it is merged.

# 📄 License
By contributing, you agree that your contributions will be licensed under the MIT License.

Thank you for your contributions! 🥳

If you have any questions, feel free to reach out by opening an issue or joining the discussion.