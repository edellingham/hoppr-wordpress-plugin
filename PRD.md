# Hoppr WordPress Plugin - Product Requirements Document

## Overview

**Product Name:** Hoppr  
**Version:** 1.0.0  
**Author:** Cloud Nine Web (https://cloudnineweb.co)
**Target Platform:** WordPress 5.0+  
**Primary Technologies:** PHP, JavaScript, CSS, HTML  
**Target Audience:** General site owners, marketers, content managers

Hoppr is a WordPress plugin that enables users to create and manage 301/302 redirects with comprehensive analytics tracking and QR code generation capabilities. The plugin provides a modern, clean interface for redirect management with detailed traffic insights.

## Core Features

### 1. Redirect Management

#### 1.1 Redirect Creation
- **Redirect Types:** Support for 301 (permanent) and 302 (temporary) redirects
- **URL Matching:** Exact URL matching with option to preserve or ignore query strings
- **Query String Handling:** Option to pass existing query strings to destination URL
- **Bulk Operations:** Import/export functionality via CSV format
- **Validation:** URL validation for both source and destination URLs
- **Status Management:** Enable/disable individual redirects without deletion

#### 1.2 Redirect Interface
- Clean, modern admin interface following WordPress design standards
- Sortable table view with search/filter capabilities
- Quick edit functionality for common changes
- Bulk actions (enable/disable/delete multiple redirects)

### 2. Analytics & Tracking

#### 2.1 Data Collection
- **Click Counts:** Track total clicks per redirect
- **Geographic Data:** Country-level geographic tracking
- **Device Information:** Device type detection (Desktop, Mobile, Tablet)
- **Timestamps:** Detailed timestamp logging for each click
- **Referrer Tracking:** Track referring domains/pages

#### 2.2 Analytics Dashboard
- **Overview Dashboard:** Site-wide redirect analytics summary
- **Individual Redirect Analytics:** Detailed charts for each redirect
- **Chart Types:** 
  - Click trends over time (line chart)
  - Geographic distribution (bar chart/map visualization)
  - Device type breakdown (pie chart)
  - Top referrers (bar chart)
- **Date Range Filtering:** Custom date range selection for all analytics views
- **Export Capabilities:** Export analytics data to CSV

#### 2.3 Data Retention
- **Configurable Retention:** Setting to control how long analytics data is stored
- **Retention Options:** 30 days, 90 days, 1 year, forever
- **Automatic Cleanup:** Scheduled cleanup of old data based on retention setting

### 3. QR Code Generation

#### 3.1 QR Code Features
- **Format Support:** Generate QR codes in PNG and SVG formats
- **Pre-generation:** QR codes generated and stored upon redirect creation/update
- **Regeneration:** Ability to regenerate QR codes when destination URL changes
- **Download Options:** Direct download links for both PNG and SVG formats
- **Preview:** QR code preview in the redirect management interface

#### 3.2 QR Code Storage
- QR codes stored in WordPress uploads directory under `/hoppr/qr-codes/`
- Organized by redirect ID for easy management
- Automatic cleanup when redirects are deleted

### 4. User Management & Permissions

#### 4.1 Role-Based Access
- **Configurable Permissions:** Admin can set which user roles can:
  - View redirects
  - Create/edit redirects
  - Delete redirects
  - View analytics
  - Access settings
- **Default Permissions:** Administrator role has full access by default

#### 4.2 Settings Panel
- **User Role Configuration:** Interface to set permissions per WordPress role
- **Data Retention Settings:** Configure analytics data retention period
- **General Settings:** Plugin-wide configuration options

## Technical Specifications

### 5. Database Schema

#### 5.1 Redirects Table (`wp_hoppr_redirects`)
```sql
id (bigint, primary key, auto_increment)
source_url (varchar 255, unique)
destination_url (text)
redirect_type (int, 301 or 302)
preserve_query_strings (boolean)
status (varchar 20, 'active'/'inactive')
created_date (datetime)
modified_date (datetime)
created_by (bigint, foreign key to wp_users)
```

#### 5.2 Analytics Table (`wp_hoppr_analytics`)
```sql
id (bigint, primary key, auto_increment)
redirect_id (bigint, foreign key to wp_hoppr_redirects)
click_timestamp (datetime)
ip_address (varchar 45, hashed for privacy)
country_code (varchar 2)
device_type (varchar 20)
referrer (text)
user_agent (text)
```

#### 5.3 QR Codes Table (`wp_hoppr_qr_codes`)
```sql
id (bigint, primary key, auto_increment)
redirect_id (bigint, foreign key to wp_hoppr_redirects)
png_file_path (varchar 255)
svg_file_path (varchar 255)
generated_date (datetime)
```

### 6. Performance & Caching

#### 6.1 Caching Strategy
- **Redirect Rules Caching:** Cache active redirects in WordPress object cache
- **Cache Invalidation:** Automatically clear cache when redirects are modified
- **Database Optimization:** Proper indexing on source_url and redirect_id columns
- **Analytics Optimization:** Batch analytics inserts to reduce database load

#### 6.2 Performance Considerations
- Limit to ~100 active redirects for optimal performance
- Use WordPress Transients API for temporary data storage
- Optimize redirect matching with efficient string comparison
- Background processing for analytics data aggregation

### 7. User Interface Requirements

#### 7.1 Design Standards
- **WordPress Native:** Follow WordPress admin design patterns and conventions
- **Responsive Design:** Fully responsive interface for mobile admin access
- **Modern UI Elements:** Clean, modern design with proper spacing and typography
- **Accessibility:** WCAG 2.1 AA compliance for accessibility

#### 7.2 Page Structure
- **Main Dashboard:** Overview with summary statistics and recent activity
- **Redirects Management:** Main redirect creation and management interface
- **Analytics:** Detailed analytics dashboard with charts and filters
- **Settings:** Plugin configuration and user permission management

#### 7.3 JavaScript Framework
- Use WordPress standard jQuery for frontend interactions
- Chart.js for analytics visualizations
- Native WordPress AJAX for form submissions

### 8. Security Requirements

#### 8.1 Input Validation
- Sanitize all URL inputs to prevent XSS attacks
- Validate redirect destinations to prevent open redirects to malicious sites
- WordPress nonce verification for all admin actions

#### 8.2 Data Protection
- Hash IP addresses before storing for privacy protection
- Proper WordPress capability checks for all admin functions
- Sanitize and escape all output data

### 9. File Structure

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
├── admin/
│   ├── css/
│   │   └── hoppr-admin.css
│   ├── js/
│   │   ├── hoppr-admin.js
│   │   └── chart.min.js
│   └── partials/
│       ├── hoppr-admin-dashboard.php
│       ├── hoppr-admin-redirects.php
│       ├── hoppr-admin-analytics.php
│       └── hoppr-admin-settings.php
├── public/
│   └── class-hoppr-public.php (frontend redirect handling)
└── vendor/
    └── (QR code generation library)
```

### 10. Third-Party Dependencies

#### 10.1 QR Code Generation
- Use PHP QR Code library (endroid/qr-code) or similar for QR generation
- Include via Composer or bundle with plugin

#### 10.2 Analytics Visualization
- Chart.js for creating responsive analytics charts
- Include via CDN or bundle with plugin

### 11. Installation & Activation

#### 11.1 Installation Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

#### 11.2 Activation Process
- Create database tables on activation
- Set default settings and permissions
- Create uploads directory for QR codes

### 12. Future Considerations

#### 12.1 Potential Enhancements
- Regex pattern matching for source URLs
- Custom redirect codes (307, 308)
- A/B testing capabilities
- Integration with Google Analytics
- REST API endpoints for external integrations

#### 12.2 Scalability
- Consider transition to custom database tables for large installations
- Implement background processing for heavy analytics workloads
- Add caching layers for high-traffic scenarios

## Success Metrics

- **Usability:** Clean, intuitive interface that requires minimal learning curve
- **Performance:** Page load times under 2 seconds for admin interfaces
- **Reliability:** 99.9% uptime for redirect functionality
- **Data Accuracy:** Accurate analytics tracking with minimal data loss

## Acceptance Criteria

1. Users can create, edit, and delete 301/302 redirects through an intuitive interface
2. All redirect traffic is accurately tracked with geographic, device, and temporal data
3. QR codes are automatically generated in PNG and SVG formats for each redirect
4. Analytics dashboard provides clear, actionable insights with interactive charts
5. Role-based permissions work correctly with configurable access levels
6. Bulk import/export functionality works reliably with CSV format
7. Plugin integrates seamlessly with existing WordPress installations
8. All data is stored securely with proper sanitization and validation