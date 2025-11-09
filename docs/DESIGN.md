# Design Documentation

## Color Scheme

### Primary Colors
- **Primary**: `#7b2d2d` (Dark Red/Burgundy)
- **Primary Dark**: `#5a1f1f`
- **Primary Light**: `#9a3a3a`

### Secondary Colors
- **Background**: `#faf7f5` (Light Beige)
- **Text**: `#2b1b1b` (Dark Brown)
- **Text Muted**: `#7a6f6f` (Gray)
- **Border**: `#eadfda` (Light Beige)

### Accent Colors
- **Success**: `#28a745` (Green)
- **Danger**: `#dc3545` (Red)
- **Warning**: `#ffc107` (Yellow)

## Typography

### Font Family
- **Primary**: System UI fonts (system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif)
- Ensures native look and feel across platforms

### Font Sizes
- **Hero Title**: 3rem (48px)
- **Page Title**: 2rem (32px)
- **Card Title**: 1.25rem (20px)
- **Body Text**: 1rem (16px)
- **Small Text**: 0.9rem (14px)

## UI/UX Guidelines

### Layout
- **Container**: Max width with centered content
- **Grid System**: Bootstrap 5 grid (12 columns)
- **Spacing**: Consistent padding and margins using Bootstrap spacing utilities

### Components

#### Cards
- Border radius: 10px
- Hover effect: Slight elevation and shadow
- Image height: 200px (desktop), 150px (mobile)

#### Buttons
- Primary: Dark red background with white text
- Hover: Darker shade with slight elevation
- Border radius: 8px

#### Forms
- Input fields: Border radius 6px
- Focus state: Primary color border with shadow
- Validation: Red border and error message for invalid inputs

### Responsive Design

#### Breakpoints (Bootstrap 5)
- **Mobile**: < 576px
- **Tablet**: 576px - 768px
- **Desktop**: > 768px

#### Mobile Optimizations
- Reduced font sizes
- Stacked layouts
- Touch-friendly button sizes
- Collapsible navigation

### Accessibility

#### Color Contrast
- All text meets WCAG AA contrast requirements
- Primary text on white: 12:1 ratio
- Muted text: 4.5:1 ratio

#### ARIA Roles
- Navigation: `role="navigation"`
- Main content: `role="main"`
- Forms: Proper labels and error messages

#### Alt Text
- All images have descriptive alt text
- Decorative images use empty alt text

## Wireframes

### Homepage
```
┌─────────────────────────────────────┐
│         Navigation Bar              │
├─────────────────────────────────────┤
│         Hero Section                │
│    (Welcome Message + CTA)          │
├─────────────────────────────────────┤
│    Featured Destinations (4 cards)  │
├─────────────────────────────────────┤
│    Top Hotels (4 cards)             │
├─────────────────────────────────────┤
│    Top Flights (4 cards)            │
├─────────────────────────────────────┤
│    Top Activities (4 cards)         │
├─────────────────────────────────────┤
│         Footer                      │
└─────────────────────────────────────┘
```

### Listing Page
```
┌─────────────────────────────────────┐
│         Navigation Bar              │
├─────────────────────────────────────┤
│         Page Title                  │
├─────────────────────────────────────┤
│    Filter Section                   │
│    (Search, Filters, Buttons)      │
├─────────────────────────────────────┤
│    Results Grid (3 columns)         │
│    [Card] [Card] [Card]             │
│    [Card] [Card] [Card]             │
├─────────────────────────────────────┤
│         Footer                      │
└─────────────────────────────────────┘
```

### Detail Page
```
┌─────────────────────────────────────┐
│         Navigation Bar              │
├─────────────────────────────────────┤
│    [Large Image]                    │
├──────────────────┬──────────────────┤
│  Details Section │  Booking Form    │
│  (Left Column)   │  (Right Column)  │
│                  │                  │
│                  │                  │
└──────────────────┴──────────────────┘
│         Footer                      │
└─────────────────────────────────────┘
```

## Animations

### Transitions
- **Hover Effects**: 0.2s ease
- **Card Hover**: Transform translateY(-4px) with shadow
- **Button Hover**: Background color change with elevation

### Loading States
- Spinner animation for async operations
- Fade-in animation for loaded content

## Branding Elements

### Logo
- Text-based logo: "Travel Booking"
- Font weight: 700 (Bold)
- Color: White (on primary background)

### Icons
- Bootstrap Icons (if needed)
- Font Awesome (if needed)

### Images
- Aspect ratio: 16:9 for hero images
- Aspect ratio: 4:3 for card images
- Format: JPEG, PNG, or WebP
- Max size: 5MB per image


