# Wistia Channel Gallery

A WordPress plugin that displays Wistia channels and video galleries with a beautiful, responsive layout. Features include automatic video fetching from Wistia channels, custom video galleries, sharing functionality, and more.

## Features

- **Channel Support**: Automatically fetch and display videos from Wistia channels
- **Custom Video Galleries**: Create galleries from manually entered video IDs
- **Main Video + Gallery Layout**: Display a featured video at the top with a gallery of related videos below
- **Video Sharing**: Share individual videos with direct links that open the specific video
- **Responsive Design**: Fully responsive layout that works on all devices
- **Automatic Updates**: Gallery automatically updates when videos are added or removed from the channel
- **Video Descriptions**: Display video titles and descriptions
- **Sorting**: Videos are sorted from newest to oldest
- **No API Token Required**: Works with public channels and videos from any Wistia account

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wistia-playlist-gallery` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Wistia Channel** in the WordPress admin menu to configure

## Configuration

### Setting Up API Token (Optional but Recommended)

1. Go to **Wistia Channel > Settings** in WordPress admin
2. Enter your Wistia API Token (found in your Wistia account settings)
3. Set a default Channel ID if desired
4. Click **Save Changes**

**Note**: The API token is required to automatically fetch videos from channels. Without it, you can still use the plugin by manually entering video IDs.

## Usage

### Shortcode: Channel Gallery

Display all videos from a Wistia channel:

```
[wistia_playlist_gallery channel_id="bkfd9ulu5l"]
```

**Parameters:**
- `channel_id` (required): The Wistia channel ID (e.g., `bkfd9ulu5l` from URL `https://fast.wistia.com/embed/channel/bkfd9ulu5l`)

**Requirements:**
- API Token must be configured in settings
- Channel must belong to your Wistia account
- Channel must be "Unlocked" in Wistia settings

### Shortcode: Custom Video Gallery

Display a custom gallery from manually entered video IDs:

```
[wistia_playlist_gallery video_ids="abc123,def456,ghi789"]
```

**Parameters:**
- `video_ids` (required): Comma-separated list of Wistia video hashed IDs

**Advantages:**
- Works with videos from any Wistia account
- No API token required
- Works with public videos

### Finding Video IDs

You can find the video hashed ID in the Wistia video URL:
- Example: `https://wistia.com/medias/abc123` → Video ID is `abc123`

## Features Explained

### Main Video + Gallery Layout

When using the shortcode, the plugin displays:
- **Main Video**: The first (newest) video displayed large at the top
- **Gallery**: Remaining videos displayed in a responsive grid below

### Video Sharing

Each video has a "Condividi puntata" (Share Episode) button that:
- Located below each video description, left-aligned
- Red background with white text, no rounded corners
- Generates a shareable link with the video ID
- Copies the link to clipboard automatically
- Shows "Link copiato!" confirmation message
- When someone opens the shared link, that specific video is displayed as the main video

**Example shared link:**
```
https://yoursite.com/page/?video=abc123
```

### Video Sorting

Videos are automatically sorted from **newest to oldest** based on their creation date.

### Video Descriptions

The plugin displays:
- Video title (from Wistia)
- Video description (if available in Wistia)

Descriptions are shown below each video title.

## Admin Interface

### Settings Page

Located at **Wistia Channel > Settings**:
- **Default Channel ID**: Set a default channel to use if not specified in shortcode
- **Wistia API Token**: Required for channel functionality

### Generate Shortcode Page

Located at **Wistia Channel > Genera Shortcode**:

**Option 1: Channel (requires API Token)**
- Select or enter a channel ID
- Automatically generates shortcode for that channel
- Shows channel information and video count

**Option 2: Manual Video IDs**
- Enter video IDs manually (comma-separated)
- Works with videos from any Wistia account
- No API token required

## Styling

The plugin includes responsive CSS that:
- Displays videos in a clean, modern grid layout
- Adapts to mobile, tablet, and desktop screens
- Features hover effects and smooth transitions
- Uses a red color scheme throughout:
  - **Wistia Player**: Customized with red player colors (`#dc3545`)
  - **Share Buttons**: Red background with white text, left-aligned
  - **No rounded corners**: Clean, straight edges for buttons

### Custom Styling

You can override the plugin styles by adding custom CSS to your theme. The plugin uses these CSS classes:

- `.wpg-container` - Main container
- `.wpg-main-video` - Featured video section
- `.wpg-gallery` - Video gallery grid
- `.wpg-item` - Individual video item
- `.wpg-share-button` - Share button
- `.wpg-video-title` - Video title
- `.wpg-video-description` - Video description

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Wistia account (for channel functionality)
- Wistia API Token (optional, for channel auto-fetching)

## API Token Setup

1. Log in to your Wistia account
2. Go to **Account Settings > API**
3. Create a new API token or use an existing one
4. Copy the token
5. Paste it in the plugin settings

**Token Permissions:**
- `media:read` - Required to read video information
- `channel:read` - Required to read channel information

## Troubleshooting

### Videos Not Showing

**For Channels:**
- Verify API token is correct
- Ensure channel is "Unlocked" in Wistia settings
- Check that channel belongs to your Wistia account
- Verify channel ID is correct

**For Manual Video IDs:**
- Verify video IDs are correct (hashed_id from Wistia URL)
- Ensure videos are public or accessible

### Description Not Showing

- Descriptions are only shown if available in Wistia
- The plugin checks multiple fields: `description`, `caption`, `seoDescription`
- If no description is set in Wistia, it won't be displayed

### Share Button Not Working

- Ensure JavaScript is enabled in the browser
- Check browser console for errors
- Verify the page URL is correct

## Limitations

- Channel functionality requires API token and only works with channels from your own Wistia account
- For channels from other accounts, use manual video IDs
- API rate limit: 600 requests per minute (per Wistia account)

## Support

For issues or questions:
- Check the WordPress admin error messages (visible to administrators)
- Verify your Wistia account settings
- Ensure API token has correct permissions

## Changelog

### Version 1.0
- Initial release
- Channel support with API integration
- Custom video gallery support
- Main video + gallery layout
- Video sharing functionality with direct links
- Responsive design
- Video descriptions and sorting (newest to oldest)
- Red color scheme for Wistia players
- Left-aligned share buttons with red styling
- Automatic video fetching from channels
- Support for videos from any Wistia account

## License

This plugin is developed by DWAY Agency.

## Credits

- Built for WordPress
- Uses Wistia API v1
- Responsive CSS Grid layout
