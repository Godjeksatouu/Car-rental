# 🤝 Contributing to AutoDrive Car Rental System

Thank you for your interest in contributing to AutoDrive! This document provides guidelines and information for contributors.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Contribution Guidelines](#contribution-guidelines)
- [Coding Standards](#coding-standards)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)

## 📜 Code of Conduct

### Our Pledge
We are committed to making participation in this project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards
- **Be respectful** and inclusive in your language and actions
- **Be collaborative** and help others learn and grow
- **Be constructive** when giving feedback
- **Focus on what is best** for the community and project

## 🚀 Getting Started

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Git for version control
- Basic knowledge of PHP, HTML, CSS, and JavaScript

### Fork and Clone
1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/yourusername/Car-rental.git
   cd Car-rental
   ```

3. Add the original repository as upstream:
   ```bash
   git remote add upstream https://github.com/Godjeksatouu/Car-rental.git
   ```

## 🛠️ Development Setup

### Local Environment
1. **Set up web server** (XAMPP, WAMP, or LAMP)
2. **Create database** and import `database/car_rental.sql`
3. **Configure** `includes/config.php` with your database credentials
4. **Test** the application locally

### Development Workflow
1. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** following our coding standards
3. **Test thoroughly** on different browsers and devices
4. **Commit with clear messages**:
   ```bash
   git commit -m "Add: Brief description of your changes"
   ```

## 📝 Contribution Guidelines

### Types of Contributions

#### 🐛 Bug Fixes
- Fix existing functionality that isn't working correctly
- Include steps to reproduce the bug
- Add tests if applicable

#### ✨ New Features
- Add new functionality that enhances the system
- Discuss major features in an issue first
- Include documentation for new features

#### 📚 Documentation
- Improve existing documentation
- Add missing documentation
- Fix typos and grammar

#### 🎨 UI/UX Improvements
- Enhance user interface design
- Improve user experience
- Ensure mobile responsiveness

### What We're Looking For
- **Security improvements** - Better authentication, input validation
- **Performance optimizations** - Faster queries, better caching
- **Code quality** - Cleaner, more maintainable code
- **New features** - Additional functionality for users and admins
- **Bug fixes** - Resolving existing issues
- **Documentation** - Better guides and code comments

## 💻 Coding Standards

### PHP Standards
- Follow **PSR-12** coding style
- Use **meaningful variable names**: `$user_data` instead of `$ud`
- Add **comprehensive comments** for complex logic
- Use **prepared statements** for all database queries

#### Example:
```php
/**
 * Calculate total rental price for a car reservation
 * 
 * @param int $car_id The ID of the car being rented
 * @param string $start_date Start date in YYYY-MM-DD format
 * @param string $end_date End date in YYYY-MM-DD format
 * @param mysqli $database_connection Database connection object
 * @return float Total rental price
 */
function calculateCarRentalPrice($car_id, $start_date, $end_date, $database_connection) {
    // Implementation with clear comments
}
```

### HTML/CSS Standards
- Use **semantic HTML5** elements
- Follow **BEM methodology** for CSS classes
- Ensure **accessibility** with proper ARIA labels
- Maintain **responsive design** principles

### JavaScript Standards
- Use **modern ES6+** syntax when possible
- Add **JSDoc comments** for functions
- Follow **consistent naming** conventions
- Avoid **global variables**

### Database Standards
- Use **descriptive table and column names**
- Follow **normalization principles**
- Add **proper indexes** for performance
- Include **foreign key constraints**

## 🔄 Pull Request Process

### Before Submitting
1. **Update your branch** with the latest upstream changes:
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

2. **Test thoroughly**:
   - All existing functionality works
   - New features work as expected
   - No PHP errors or warnings
   - Responsive design is maintained

3. **Update documentation** if needed

### Pull Request Template
When creating a pull request, include:

```markdown
## Description
Brief description of changes made

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Documentation update
- [ ] Performance improvement
- [ ] Code refactoring

## Testing
- [ ] Tested on Chrome/Firefox/Safari
- [ ] Tested on mobile devices
- [ ] All existing tests pass
- [ ] Added new tests for new functionality

## Screenshots (if applicable)
Add screenshots of UI changes

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] No breaking changes introduced
```

### Review Process
1. **Automated checks** will run on your PR
2. **Maintainers will review** your code
3. **Address feedback** promptly and professionally
4. **Squash commits** if requested before merging

## 🐛 Issue Reporting

### Bug Reports
When reporting bugs, include:
- **Clear title** describing the issue
- **Steps to reproduce** the problem
- **Expected behavior** vs actual behavior
- **Environment details** (PHP version, browser, OS)
- **Screenshots** if applicable

### Feature Requests
For new features, include:
- **Clear description** of the proposed feature
- **Use case** explaining why it's needed
- **Possible implementation** ideas
- **Mockups or wireframes** if applicable

### Issue Labels
We use these labels to categorize issues:
- `bug` - Something isn't working
- `enhancement` - New feature or improvement
- `documentation` - Documentation needs
- `good first issue` - Good for newcomers
- `help wanted` - Extra attention needed
- `priority: high` - Critical issues

## 🏆 Recognition

### Contributors
All contributors will be:
- **Listed** in the project's contributors section
- **Credited** in release notes for significant contributions
- **Thanked** publicly for their efforts

### Becoming a Maintainer
Regular contributors who demonstrate:
- **Consistent quality** contributions
- **Good understanding** of the project
- **Helpful community** participation

May be invited to become project maintainers.

## 📞 Getting Help

### Communication Channels
- **GitHub Issues** - For bugs and feature requests
- **Email** - godjeksatou@gmail.com for direct contact
- **Code Review** - Through pull request comments

### Resources
- **Project Documentation** - README.md and code comments
- **PHP Documentation** - https://www.php.net/docs.php
- **MySQL Documentation** - https://dev.mysql.com/doc/

## 🎯 Development Priorities

### Current Focus Areas
1. **Security enhancements** - Improving authentication and data protection
2. **Performance optimization** - Faster page loads and database queries
3. **Mobile experience** - Better responsive design
4. **Admin features** - More comprehensive management tools
5. **Code documentation** - Better inline documentation

### Future Roadmap
- **API development** - RESTful API for mobile apps
- **Payment integration** - Real payment processing
- **Multi-language support** - Internationalization
- **Advanced reporting** - Analytics and insights
- **Email notifications** - Automated customer communications

---

**Thank you for contributing to AutoDrive! Together, we can build an amazing car rental management system.** 🚗✨
