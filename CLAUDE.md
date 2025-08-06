# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Hoppr is a WordPress plugin for creating and managing 301/302 redirects with comprehensive analytics tracking and QR code generation. This is currently in early development stage - only PRD.md exists with no actual plugin code yet.

**Version:** 1.0.0  
**Author:** Cloud Nine Web (https://cloudnineweb.co)  
**Target Platform:** WordPress 5.0+  
**Primary Technologies:** PHP, JavaScript, CSS, HTML

## Development Context

This is a **new plugin development project** starting from scratch. The repository currently contains:
- `PRD.md` - Complete Product Requirements Document with detailed specifications
- `memories.json` - Empty ken-you-remember memory storage file

## Planned Architecture (from PRD)

### Core Plugin Structure
```
hoppr/
├── hoppr.php (main plugin file)
├── includes/
│   ├── class-hoppr.php (main plugin class)
│   ├── class-hoppr-redirects.php (redirect management)
│   ├── class-hoppr-analytics.php (analytics functionality)
│   ├── class-hoppr-qr-codes.php (QR code generation)
│   ├── class-hoppr-admin.php (admin interface)
│   └── class-hoppr-settings.php (settings management)
├── admin/ (CSS/JS/templates for admin interface)
├── public/ (frontend redirect handling)
└── vendor/ (QR code generation library)
```

### Database Schema
Three main tables planned:
- `wp_hoppr_redirects` - Core redirect storage
- `wp_hoppr_analytics` - Click tracking and analytics
- `wp_hoppr_qr_codes` - QR code file paths and metadata

### Key Features to Implement
1. **Redirect Management** - 301/302 redirects with query string handling
2. **Analytics Dashboard** - Geographic, device, referrer tracking with Chart.js
3. **QR Code Generation** - PNG/SVG format using PHP QR library
4. **Role-based Permissions** - Configurable user access levels
5. **Bulk Operations** - CSV import/export functionality

## WordPress Plugin Development Standards

### File Organization
- Follow WordPress plugin directory structure conventions
- Use WordPress coding standards (WPCS)
- Implement proper plugin header in main file
- Use WordPress hooks and filters appropriately

### Database Operations
- Use WordPress $wpdb for database operations
- Create tables on plugin activation
- Implement proper table cleanup on deactivation
- Use WordPress transients for caching

### Security Requirements
- Sanitize all inputs using WordPress functions
- Use WordPress nonces for form submissions
- Implement proper capability checks
- Hash IP addresses for privacy protection
- Validate redirect destinations to prevent open redirects

### Frontend Integration
- Use WordPress jQuery (don't enqueue separate jQuery)
- Follow WordPress admin UI/UX patterns
- Implement responsive design for mobile admin
- Use WordPress AJAX for asynchronous operations

## Development Dependencies

### PHP Libraries
- QR Code generation library (endroid/qr-code or similar)
- Composer for dependency management (if using external libraries)

### JavaScript Libraries
- Chart.js for analytics visualizations
- WordPress native jQuery for interactions

### WordPress Requirements
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

## Performance Considerations

### Optimization Guidelines
- Cache active redirects using WordPress object cache
- Limit to ~100 active redirects for optimal performance
- Use background processing for analytics aggregation
- Implement proper database indexing on lookup columns
- Batch analytics inserts to reduce database load

### Caching Strategy
- Use WordPress Transients API for temporary data storage
- Cache redirect rules and invalidate on modifications
- Optimize redirect matching with efficient algorithms

## Development Workflow

Since this is a fresh WordPress plugin development:

1. **Start with main plugin file** (`hoppr.php`) with proper WordPress headers
2. **Create database schema** and activation/deactivation hooks
3. **Build core redirect functionality** before adding advanced features
4. **Implement admin interface** following WordPress admin patterns
5. **Add analytics tracking** as secondary feature
6. **Integrate QR code generation** last

## WordPress Integration Notes

- Plugin will be installed in WordPress plugins directory
- Must integrate with WordPress admin menu system
- Should follow WordPress plugin guidelines for submission to repository
- Implement proper internationalization (i18n) for text strings
- Use WordPress settings API for configuration options

## Testing Approach

- Test on WordPress 5.0+ environments
- Verify compatibility with common WordPress themes
- Test redirect functionality across different browsers
- Validate analytics accuracy
- Performance testing with various redirect volumes
- Security testing for input validation and access controls