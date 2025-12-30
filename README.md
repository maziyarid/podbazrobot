# Podbaz Robot 🎨

**Persian WordPress Plugin for AI-Powered HTML Content Generation**

## Description

Podbaz Robot is a sophisticated WordPress plugin designed for generating rich, colorful HTML content for WooCommerce products and blog posts using AI. It's specifically tailored for Persian (Farsi) vaping product websites but can be adapted for any Persian e-commerce site.

## Features

### ✨ Core Features
- 🎨 **Rich HTML Generation**: Creates beautiful, colorful HTML content with proper RTL support
- 📦 **WooCommerce Integration**: Automatically creates products with complete specifications
- 📝 **Blog Post Generation**: Creates SEO-optimized blog posts with structured HTML
- 🔄 **Content Update**: Update and enhance existing products and posts
- 🔍 **AI-Powered Research**: Integrated with Tavily API for automatic product research
- 🤖 **Multiple AI Models**: Support for Claude Sonnet 4, GPT-4o, and other models via Blackbox AI

### 📋 Output Includes
- ✅ SEO metadata (title, description, slug)
- ✅ Structured HTML with color-coded sections
- ✅ Technical specifications tables
- ✅ Custom fields for product attributes
- ✅ Image alt texts
- ✅ Brand story sections
- ✅ Safety and usage information

### 🎨 Design Features
- Beautiful color palette for different sections
- Mobile-responsive HTML output
- RTL (Right-to-Left) support for Persian text
- Emoji-enhanced headers
- Professional table layouts

## Installation

1. Upload the plugin files to `/wp-content/plugins/podbazrobot/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure API keys in Settings → Podbaz Robot → Settings
4. Start generating content!

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce 5.0+ (for product generation)
- API keys:
  - Blackbox AI API key ([Get it here](https://www.blackbox.ai/api))
  - Tavily API key ([Get it here](https://tavily.com))

## Configuration

### API Settings

1. Navigate to **Podbaz Robot → Settings**
2. Enter your Blackbox AI API key
3. Enter your Tavily API key
4. Select your preferred AI model
5. Test connections to verify

### Customizing Prompts

1. Go to **Podbaz Robot → Prompts**
2. Edit any of the four prompt types:
   - Research Prompt (for product research)
   - Product HTML Prompt (for product page generation)
   - Post HTML Prompt (for blog posts)
   - Update Prompt (for content updates)
3. Save changes

## Usage

### Creating a New Product

1. Go to **Podbaz Robot → New Product**
2. Enter the product name (e.g., "VAPORESSO XROS 4")
3. Add target keywords (optional)
4. Choose research method:
   - **Auto**: Let Tavily API research automatically
   - **Manual**: Paste your own research data
5. Select publish status (Draft or Publish)
6. Click "Generate Product HTML"
7. Wait for the AI to generate content (2-3 minutes)
8. Product will be created in WooCommerce with all fields populated

### Creating a Blog Post

1. Go to **Podbaz Robot → New Post**
2. Enter the post topic
3. Add keywords
4. Select post type (Guide, Review, Comparison, etc.)
5. Choose research method
6. Click "Generate Post HTML"
7. Post will be created with structured HTML content

### Updating Existing Content

1. Go to **Podbaz Robot → Update**
2. Select content type (Product or Post)
3. Choose the item to update
4. Load current content (optional preview)
5. Enter update instructions
6. Optionally refresh research data
7. Click "Update Content"

## File Structure

```
podbazrobot/
├── podbaz-robot.php           # Main plugin file
├── uninstall.php              # Cleanup on uninstall
├── includes/                  # Core classes
│   ├── class-prompts.php
│   ├── class-blackbox-api.php
│   ├── class-tavily-api.php
│   ├── class-html-parser.php
│   ├── class-custom-fields.php
│   ├── class-product-handler.php
│   └── class-post-handler.php
└── admin/                     # Admin interface
    ├── class-admin-pages.php
    ├── class-ajax-handlers.php
    ├── css/
    │   └── admin.css
    ├── js/
    │   └── admin.js
    └── views/
        ├── main-page.php
        ├── post-page.php
        ├── update-page.php
        ├── prompts-page.php
        ├── settings-page.php
        └── logs-page.php
```

## Custom Fields

The plugin automatically maps generated data to WooCommerce custom fields:

- `_product_brand` - Brand name
- `_product_model` - Model name
- `_product_country` - Country of manufacture
- `_battery_capacity` - Battery capacity
- `_output_power` - Output power
- `_tank_capacity` - Pod/tank capacity
- `_coil_resistance` - Coil resistance
- `_charging_type` - Charging port type
- `_display_type` - Display type
- `_chipset` - Chipset name
- And more...

## Logging

All plugin activities are logged in the database. View logs at **Podbaz Robot → Logs**.

Features:
- View all operations
- Filter by success/failure
- Export logs as CSV
- Clear all logs

## Security

- All AJAX requests are protected with nonces
- Proper capability checks (requires `manage_options`)
- Input sanitization and output escaping
- SQL queries use prepared statements
- No security vulnerabilities detected by CodeQL

## Uninstallation

When you delete the plugin, it will:
- Remove all plugin options
- Drop the logs database table
- Clean up all post meta created by the plugin

## Support

For issues, questions, or feature requests, please contact Podbaz Team.

## License

GPL v2 or later

## Credits

- Developed by Podbaz Team
- AI Integration: Blackbox AI & Tavily
- Designed for Persian vaping community

## Changelog

### Version 1.0.0
- Initial release
- Product generation with AI
- Post generation with AI
- Content update functionality
- Comprehensive logging system
- Multi-prompt support
- API testing tools