# UI Documentation - Admin Panel System

## Overview
This document provides detailed descriptions of the user interface for the modern admin panel system.

---

## 1. Admin Login Page (`admin/login.php`)

### Visual Design
- **Background**: Gradient background (light gray to blue-gray)
- **Login Container**: White card with rounded corners and shadow
- **Header**: Red gradient header with "Admin Panel" title and lock icon
- **Colors**: Professional red (#dc3545) for branding

### Key Features
- ✅ Modern, centered login form
- ✅ Email and password fields with icons
- ✅ Password visibility toggle (eye icon)
- ✅ "Remember me" checkbox
- ✅ Smooth animations and transitions
- ✅ Error messages displayed inline
- ✅ Loading state during authentication
- ✅ Responsive design (mobile, tablet, desktop)

### User Experience
1. User enters email and password
2. Form validates input client-side
3. Loading spinner appears on submit
4. Success: Redirects to dashboard with success message
5. Error: Displays error message with remaining attempts
6. Account locked: Shows lockout message with time remaining

### Accessibility
- ARIA labels on all inputs
- Keyboard navigation supported
- Screen reader friendly
- High contrast for readability

---

## 2. Admin Dashboard (`admin/dashboard.php`)

### Layout Structure
```
┌─────────────────────────────────────────────────┐
│ Sidebar (Blue)    │ Main Content Area         │
│                   │                             │
│ - Logo            │ Topbar (White)             │
│ - Dashboard       │ - Search                   │
│ - Properties      │ - Notifications            │
│ - Users           │ - User Menu                │
│ - Bookings        │                             │
│                   │ Statistics Cards (4)        │
│                   │ Charts (Revenue, Types)     │
│                   │ Tables (Recent Data)        │
└─────────────────────────────────────────────────┘
```

### Color-Coded Statistics Cards
1. **Total Properties** (Blue Border) - Building icon
2. **Available Properties** (Green Border) - Check circle icon
3. **Total Users** (Teal Border) - People icon
4. **Total Revenue** (Yellow Border) - Dollar icon

### Interactive Charts
#### Revenue Overview Chart (Line Chart)
- **Type**: Multi-line chart
- **Data**: 
  - Revenue (৳) - Blue line
  - Bookings count - Green line
- **Time Range**: Last 6 months
- **Features**:
  - Smooth curved lines
  - Hover tooltips
  - Dual Y-axis (revenue & bookings)
  - Responsive sizing

#### Property Types Chart (Doughnut Chart)
- **Type**: Doughnut/Pie chart
- **Data**: Distribution by property type
- **Colors**: Blue, Green, Teal, Yellow, Red
- **Features**:
  - Interactive hover effects
  - Legend at bottom
  - Percentage display

### Recent Data Tables
1. **Recent Properties**
   - Columns: Title, Price, Status, Date
   - Status badges (green=available, yellow=rented)
   - "View All" button links to properties page

2. **Recent Users**
   - Columns: Name, Email, Joined Date
   - Clean table design
   - "View All" button links to users page

3. **Recent Bookings** (if data exists)
   - Columns: Property, User, Check-In, Check-Out, Amount, Status
   - Status badges (success, warning, danger, info)
   - "View All" button links to bookings page

### Responsive Behavior
- **Desktop (>768px)**: Sidebar fixed, content shifts right
- **Tablet/Mobile (<768px)**: Sidebar hidden, hamburger menu
- **Cards**: Stack vertically on mobile
- **Tables**: Horizontal scroll on small screens

---

## 3. Properties Management (`admin/properties.php`)

### Page Header
- **Title**: "Manage Properties"
- **Action Button**: "+ Add New Property" (Blue button)

### Search & Filter Bar
- **Search Input**: Free text search (title, location, address)
- **Status Filter**: Dropdown (All, Available, Rented, Maintenance)
- **Buttons**: Search (blue), Reset (gray)

### Properties Table
```
┌──────┬────────────────┬────────────┬──────────┬──────────┬────────┬────────────┬─────────┐
│ ID   │ Title          │ Location   │ Price    │ Type     │ Status │ Created    │ Actions │
├──────┼────────────────┼────────────┼──────────┼──────────┼────────┼────────────┼─────────┤
│ 1    │ Modern Apt ⭐  │ Gulshan   │ ৳150,000 │ Apartment│ ✓ Avail│ Dec 01     │ ✏️ 🗑️   │
│ 2    │ Luxury Villa   │ Uttara    │ ৳350,000 │ Villa    │ ⚠ Rent │ Dec 02     │ ✏️ 🗑️   │
└──────┴────────────────┴────────────┴──────────┴──────────┴────────┴────────────┴─────────┘
```

### Features
- **Featured Badge**: Yellow star icon for featured properties
- **Status Badges**: Color-coded (green, yellow, red)
- **Action Buttons**:
  - Blue pencil icon - Edit
  - Red trash icon - Delete
- **Pagination**: Page numbers at bottom
- **Hover Effects**: Row highlighting

### Add/Edit Property Modal
- **Size**: Large modal dialog
- **Sections**:
  1. Basic Info: Title, Description
  2. Pricing: Price field with currency symbol
  3. Location: Location and Full Address
  4. Details: Bedrooms, Bathrooms, Area (sqft)
  5. Type & Status: Dropdowns

### Delete Confirmation Modal
- **Warning Message**: "Are you sure...?"
- **Danger Text**: "This action cannot be undone"
- **Buttons**: Cancel (gray), Delete (red)

---

## 4. Users Management (`admin/users.php`)

### Page Header
- **Title**: "Manage Users"
- **Info**: Total user count displayed

### Search Bar
- **Input**: Search by name or email
- **Buttons**: Search, Reset

### Users Table
```
┌────┬──────────────┬────────────────────┬─────────────┬────────┬────────────┬─────────┐
│ ID │ Name         │ Email              │ Phone       │ Status │ Joined     │ Actions │
├────┼──────────────┼────────────────────┼─────────────┼────────┼────────────┼─────────┤
│ 1  │ John Doe     │ john@example.com   │ +880123...  │✓Active │ Nov 15     │ 🔄 🗑️   │
│ 2  │ Jane Smith   │ jane@example.com   │ N/A         │✗Inactive│Nov 20     │ 🔄 🗑️   │
└────┴──────────────┴────────────────────┴─────────────┴────────┴────────────┴─────────┘
```

### Features
- **Status Badges**: Green (Active) or Red (Inactive)
- **Action Buttons**:
  - Yellow toggle icon - Toggle status
  - Red trash icon - Delete user
- **Quick Status Toggle**: Single click to activate/deactivate
- **Pagination**: For large user lists

---

## 5. Bookings Management (`admin/bookings.php`)

### Page Header
- **Title**: "Manage Bookings"
- **Info**: Total bookings count

### Filter Bar
- **Status Filter**: All, Pending, Confirmed, Cancelled, Completed
- **Buttons**: Filter, Reset

### Bookings Table
```
┌────┬───────────────┬──────────────┬──────────┬───────────┬──────────┬─────────┬─────────┐
│ ID │ Property      │ User         │ Check-In │ Check-Out │ Amount   │ Status  │ Actions │
├────┼───────────────┼──────────────┼──────────┼───────────┼──────────┼─────────┼─────────┤
│ 1  │ Modern Apt    │ John Doe     │ Dec 10   │ Dec 15    │ ৳15,000  │ Pending │ ✏️ 🗑️   │
│    │               │ john@...     │          │           │          │         │         │
│ 2  │ Luxury Villa  │ Jane Smith   │ Dec 20   │ Dec 25    │ ৳35,000  │Confirmed│ ✏️ 🗑️   │
│    │               │ jane@...     │          │           │          │         │         │
└────┴───────────────┴──────────────┴──────────┴───────────┴──────────┴─────────┴─────────┘
```

### Status Color Coding
- **Confirmed**: Green badge
- **Pending**: Yellow badge
- **Cancelled**: Red badge
- **Completed**: Blue/Teal badge

### Update Status Modal
- **Field**: Status dropdown (Pending, Confirmed, Cancelled, Completed)
- **Buttons**: Cancel (gray), Update (blue)

---

## 6. Navigation & Common Elements

### Sidebar Navigation
- **Logo**: House heart icon + "House Rent" text
- **Menu Items**:
  1. Dashboard (speedometer icon)
  2. Properties (building icon) - Expandable
  3. Users (people icon)
  4. Bookings (calendar icon)
  5. View Website (globe icon) - Opens in new tab
  6. Logout (arrow icon)

### Active State
- Blue background highlight
- Bold font weight
- Current page clearly indicated

### Topbar Elements
1. **Hamburger Menu**: Mobile only, toggles sidebar
2. **Search Bar**: Global search (desktop only)
3. **Notifications**: Bell icon with badge counter
4. **User Menu**: Avatar + Name dropdown
   - Profile option
   - Settings option
   - Logout option

### Footer
- Copyright notice
- App name and year
- Minimal, non-intrusive design

---

## 7. Visual Design System

### Typography
- **Font**: Nunito (Google Fonts)
- **Sizes**:
  - Headers: 1.8rem - 3rem
  - Body: 0.85rem - 1rem
  - Small text: 0.7rem - 0.75rem
- **Weights**: 400 (regular), 600 (semi-bold), 700 (bold), 800 (extra-bold)

### Color Palette
```
Primary:   #4e73df (Blue)       - Primary actions, links
Success:   #1cc88a (Green)      - Success states, available
Info:      #36b9cc (Teal)       - Information, neutral actions
Warning:   #f6c23e (Yellow)     - Warnings, pending states
Danger:    #e74a3b (Red)        - Errors, delete actions
Light:     #f8f9fc (Off-white)  - Backgrounds
Dark:      #5a5c69 (Dark gray)  - Text, headers
```

### Shadows & Effects
- **Cards**: Soft shadow (0 0.15rem 1.75rem rgba(58, 59, 69, 0.1))
- **Hover Effects**: Lift effect (translateY(-5px)) + stronger shadow
- **Transitions**: 0.3s cubic-bezier easing
- **Border Radius**: 0.35rem standard, 2rem for pills

### Spacing System
- **Padding**: 0.5rem, 1rem, 1.5rem, 2rem
- **Margins**: 0.5rem, 1rem, 1.5rem, 2rem
- **Gaps**: 10px, 15px, 30px

---

## 8. Animations & Interactions

### Page Load
- **Fade In Up**: Statistics cards animate in
- **Number Counter**: Stat values animate from 0 to actual value
- **Chart Animation**: Charts draw on page load

### Hover Effects
- **Cards**: Lift + shadow increase
- **Buttons**: Slight lift + shadow
- **Table Rows**: Background color change
- **Links**: Underline on hover

### Click Feedback
- **Buttons**: Press down effect
- **Forms**: Border color change on focus
- **Modals**: Smooth fade in/out

### Loading States
- **Button**: Spinner replaces text
- **Tables**: Skeleton loaders (optional)
- **Charts**: Loading message while fetching

---

## 9. Responsive Breakpoints

### Desktop (>1200px)
- Full sidebar visible
- 4 columns for stat cards
- Charts side by side
- Full-width tables

### Tablet (768px - 1199px)
- Sidebar toggleable
- 2 columns for stat cards
- Charts stacked
- Tables with horizontal scroll

### Mobile (<768px)
- Sidebar hidden by default
- 1 column for all cards
- Charts full width
- Compact table view
- Hamburger menu

---

## 10. Accessibility Features

### Keyboard Navigation
- ✅ All interactive elements tabbable
- ✅ Proper focus indicators
- ✅ Escape key closes modals
- ✅ Enter key submits forms

### Screen Readers
- ✅ ARIA labels on all inputs
- ✅ Alt text on images
- ✅ Semantic HTML structure
- ✅ Role attributes on custom elements

### Visual Accessibility
- ✅ High contrast ratios (WCAG AA)
- ✅ Large clickable areas (min 44x44px)
- ✅ Clear focus indicators
- ✅ No color-only information

### Forms
- ✅ Clear labels
- ✅ Error messages announced
- ✅ Required fields marked
- ✅ Validation feedback

---

## UI Screenshots Reference

### Admin Login Page
**Desktop View:**
- Centered 420px card on gradient background
- Email field with envelope icon
- Password field with lock icon + visibility toggle
- Remember me checkbox
- Large blue "Sign In" button
- "Back to Home" link at bottom

### Dashboard - Desktop
**Layout:**
- Left sidebar (224px wide, blue gradient)
- Top bar (70px height, white)
- 4 stat cards in a row
- 2 chart cards (8/4 column split)
- 2 table cards (6/6 column split)

### Dashboard - Mobile
**Layout:**
- Hidden sidebar (toggle via hamburger)
- Top bar (user avatar only)
- Stat cards stacked vertically
- Charts stacked vertically
- Tables full width with scroll

### Properties Management
**Features:**
- Search bar at top
- Table with 8 columns
- Colorful status badges
- Action buttons (blue edit, red delete)
- Large modal for add/edit (600px wide)

### Common Themes
- Clean, minimal design
- Consistent spacing (using 8px grid)
- Smooth animations (0.3s)
- Professional color usage
- Card-based layout
- Modern, flat design aesthetic

---

**Document Version**: 1.0  
**Last Updated**: December 6, 2024  
**Design System**: Bootstrap 5 + Custom CSS
