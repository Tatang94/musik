# Music Reward System

## Overview

This is a music streaming web application with an integrated reward system that pays users for listening to music. The application allows users to play songs and earn rewards based on their listening time. The system appears to be built as a single-page web application with PHP backend API endpoints and YouTube integration for music playback.

## User Preferences

Preferred communication style: Simple, everyday language.
Data requirements: Start with real data from zero, no sample/mock data.
Admin login credentials: Username: 089663596711, Password: boar
Design preference: Modern blue-purple-brown wave color scheme with modern logo and banner ad integration
Database preference: Hosting-friendly configuration without hardcoded database names

## System Architecture

### Frontend Architecture
- **Technology**: Vanilla JavaScript with HTML/CSS
- **Design Pattern**: Class-based JavaScript (MusicReward class) managing application state
- **UI Framework**: Custom CSS with modern design principles using CSS custom properties
- **Responsive Design**: Mobile-first approach with max-width container (500px)

### Backend Architecture
- **Technology**: PHP-based REST API
- **API Structure**: RESTful endpoints under `/api/` directory
- **Session Management**: PHP sessions for user state tracking
- **Database Integration**: Likely uses a database for user balances and song tracking

### Music Integration
- **Platform**: YouTube integration for music playback
- **Playback Method**: Embedded YouTube player or API integration
- **Song Management**: Songs stored with YouTube IDs for streaming

## Key Components

### Frontend Components
1. **MusicReward Class**: Main application controller handling:
   - Music playback state management
   - Reward calculation and tracking
   - User balance management
   - Timer functionality for listening tracking

2. **UI Elements**:
   - Header with balance display
   - Song cards for music selection
   - Play/pause controls
   - Real-time reward tracking

### Backend Components
1. **API Endpoints**:
   - `play_song.php`: Handles song playback initiation
   - Additional endpoints for user management and reward processing

2. **Data Models**:
   - User accounts with balance tracking
   - Song catalog with YouTube integration
   - Listening session tracking for reward calculation

## Data Flow

1. **Song Selection**: User clicks on song card → Frontend sends request to `play_song.php`
2. **Playback Tracking**: JavaScript timer tracks listening duration
3. **Reward Calculation**: Real-time calculation of rewards based on minutes listened
4. **Balance Updates**: Periodic updates to user balance in database
5. **State Management**: Frontend maintains current song, player state, and reward metrics

## External Dependencies

### Core Dependencies
- **YouTube API**: For music streaming and playback
- **PHP**: Server-side processing and API endpoints
- **Database**: User data and song catalog storage (specific database not identified)

### Frontend Libraries
- Modern browser APIs for media handling
- Fetch API for backend communication
- No external JavaScript frameworks detected

## Deployment Strategy

### Application Structure
- **Static Assets**: CSS and JavaScript files served from `/assets/` directory
- **API Layer**: PHP scripts in `/api/` directory
- **Single Page Application**: Minimal server-side rendering with client-side state management

### Configuration Requirements
- Web server with PHP support
- Database server for user and song data
- YouTube API credentials for music integration
- Session storage configuration

### Scalability Considerations
- Frontend designed for mobile-responsive experience
- API structure allows for easy endpoint expansion
- Modular JavaScript architecture supports feature additions

The application follows a clean separation of concerns with a lightweight frontend communicating with a PHP backend, making it suitable for rapid development and deployment on standard web hosting platforms.

## Recent Changes (July 23, 2025)

### Migration Completed
- ✓ Successfully migrated from Replit Agent to standard Replit environment
- ✓ PHP server configured and running on port 5000
- ✓ Database tables initialized with setup.php
- ✓ All core functionality verified and working

### Design Updates
- ✓ Modern wave color scheme: blue-purple-brown gradient background
- ✓ Updated logo with modern SVG design
- ✓ Enhanced glassmorphism UI with backdrop blur effects
- ✓ Improved card styling with gradient backgrounds
- ✓ **NEW**: Modern landing page for "Beranda" tab with monetization messaging

### New Features
- ✓ Adsterra banner ad integration system
- ✓ Admin panel for banner management at `/admin/banner_ads.php`
- ✓ Dynamic banner loading API
- ✓ Banner display below "listen to music and earn" text
- ✓ YouTube Iframe API integration for synchronized play/pause
- ✓ Delete song functionality in admin panel
- ✓ **NEW**: Landing page with "Monetisasi Penikmat Lagu" branding
- ✓ **NEW**: Call-to-action "Mulai Sekarang" button redirecting to songs.php
- ✓ **NEW**: Feature cards highlighting rewards, music quality, and ease of use
- ✓ **NEW**: Bulk add songs feature in admin panel with checkbox selection
- ✓ **NEW**: Mass song import from YouTube channels/artists search results

### Technical Improvements
- ✓ Enhanced security with proper client/server separation
- ✓ Responsive design optimizations
- ✓ Modern CSS animations and transitions
- ✓ Error handling and LSP diagnostics addressed
- ✓ Hosting-friendly database configuration (no hardcoded names)
- ✓ Environment variable support for database paths
- ✓ Added .htaccess for security and performance
- ✓ **NEW**: Clean database starting from zero (no sample data)
- ✓ **NEW**: Removed all temporary and sample files
- ✓ **FIXED**: YouTube ads visibility issue - player now displayed properly
- ✓ **IMPROVED**: YouTube player positioned above song list without floating controls
- ✓ **FIXED**: JavaScript DOM errors with null reference checks
- ✓ **NEW**: Admin panel bulk song management with Select All/Deselect All options