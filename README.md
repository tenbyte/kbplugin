# Knowledgebase Plugin

This repository contains the `Knowledgebase Plugin`, a WordPress plugin designed to add a knowledgebase functionality to your website. The plugin allows you to create, manage, and display a collection of knowledgebase articles using custom post types, shortcodes, and other dynamic features.

## Table of Contents

1. [Features](#features)
2. [Installation](#installation)
3. [Usage](#usage)
4. [File Structure](#file-structure)
5. [Database Schema](#database-schema)

## Features

- **Custom Post Types:** Easily manage knowledgebase articles as custom post types.
- **Shortcodes:** Use shortcodes to display knowledgebase articles anywhere on your site.
- **Settings Page:** Customize plugin settings via an intuitive WordPress admin interface.
- **Authentication Handlers:** Secure your knowledgebase content with custom authentication logic.
- **Styling:** Predefined CSS styles for a polished appearance.

## Installation

1. Download the plugin files and extract them.
2. Upload the `knowledgebase-plugin` folder to the `wp-content/plugins/` directory of your WordPress installation.
3. Activate the plugin via the WordPress Admin Dashboard under `Plugins > Installed Plugins`.

## Usage

### Creating Knowledgebase Articles
1. Navigate to the `Knowledgebase` section in the WordPress Admin Dashboard.
2. Add new articles by clicking on `Add New`.
3. Use the provided fields to input the title, content, and metadata.

### Displaying Articles
Use the shortcode `[knowledgebase]` to display a list of knowledgebase articles on any page or post.

## File Structure

```
knowledgebase-plugin/
├── knowledgebase-plugin.php
├── assets/
│   └── css/
│       └── styles.css
├── includes/
    ├── shortcodes.php
    ├── settings.php
    ├── auth-handlers.php
    └── custom-post-type.php
```

### Main Files

1. **`knowledgebase-plugin.php`:**
   - The main plugin file.
   - Registers all hooks and initializes the plugin.

2. **`assets/css/styles.css`:**
   - Contains styles for the knowledgebase pages and components.

3. **`includes/shortcodes.php`:**
   - Defines the `[knowledgebase]` shortcode.

4. **`includes/settings.php`:**
   - Implements the settings page logic.

5. **`includes/auth-handlers.php`:**
   - Handles user authentication and content restrictions.

6. **`includes/custom-post-type.php`:**
   - Registers the custom post type for knowledgebase articles.

## Database Schema

Below are the SQL commands required to set up the database tables for the Knowledgebase Plugin:

```sql
-- Table for storing knowledgebase articles
CREATE TABLE wp_knowledgebase_articles (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    author_id BIGINT(20) UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('published', 'draft', 'private') DEFAULT 'draft',
    FOREIGN KEY (author_id) REFERENCES wp_users(ID) ON DELETE CASCADE
);

-- Table for article metadata (custom fields)
CREATE TABLE wp_knowledgebase_meta (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT(20) UNSIGNED NOT NULL,
    meta_key VARCHAR(255) NOT NULL,
    meta_value LONGTEXT,
    FOREIGN KEY (article_id) REFERENCES wp_knowledgebase_articles(id) ON DELETE CASCADE
);

-- Table for storing categories
CREATE TABLE wp_knowledgebase_categories (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
);

-- Table for article-category relationships
CREATE TABLE wp_knowledgebase_article_category (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT(20) UNSIGNED NOT NULL,
    category_id BIGINT(20) UNSIGNED NOT NULL,
    FOREIGN KEY (article_id) REFERENCES wp_knowledgebase_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES wp_knowledgebase_categories(id) ON DELETE CASCADE
);

```
Notes:
Replace the prefix wp_ with your actual database table prefix if it differs.
These tables are designed to integrate seamlessly with WordPress conventions and practices.

