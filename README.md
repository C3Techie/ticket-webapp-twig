# Twig/PHP Ticket Management App

A modern, responsive ticket management application built with PHP, Twig, and custom CSS (matching the React/Tailwind design).

## Features

- **Modern UI/UX**: Beautiful design with smooth animations (Animate.css)
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Authentication**: Login/signup with PHP session management
- **Ticket Management**: Full CRUD operations for tickets
- **Real-time Validation**: Server-side validation with instant feedback
- **Accessibility**: WCAG compliant with proper ARIA attributes
- **Toast Notifications**: User feedback for all actions

## Tech Stack

- **Backend**: PHP 8+
- **Templating**: Twig
- **Styling**: Custom CSS (mirrors Tailwind design system)
- **Routing**: Simple PHP router
- **Icons**: Lucide SVGs (inline)
- **Animations**: Animate.css

## Getting Started

### Prerequisites

- PHP 8.0+
- Composer

### Installation

1. Clone the repository:
```bash
git clone https://github.com/C3Techie/ticket-webapp-twig.git
ticket-webapp-twig
cd ticket-webapp-twig
```

2. Install dependencies:
```bash
composer install
```

3. Start the PHP development server:
```bash
php -S localhost:8000 -t public
```

4. Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## Project Structure

```
ticket-webapp-twig/
├── public/                # Entry point (index.php), static assets (CSS, SVG)
├── src/
│   ├── templates/         # Twig templates
│   │   ├── layout/        # Header, Footer, Hero, Base
│   │   ├── auth/          # Login, Signup
│   │   ├── dashboard/     # Dashboard, StatsCard
│   │   └── tickets/       # TicketManagement, TicketCard, TicketForm
│   ├── utils/             # Storage, validation helpers
├── storage/               # JSON files for users/tickets (simulates localStorage)
├── composer.json
├── README.md
```

---

## Example Test User Credentials

- **Demo User:**
  - Email: `demo@example.com`
  - Password: `password`

You can also sign up with a new account.

---

## Accessibility & Known Issues

- All interactive elements have visible focus states and ARIA labels.
- Color contrast meets WCAG guidelines.
- Forms are accessible to screen readers.
- If you find any accessibility issues, please open an issue or PR.

---

## Switching Between Implementations

This repository contains the **Twig/PHP** implementation.
- For **React** and **Vue.js** versions, see the root README or visit the respective repositories:
  - [ticket-webapp-react](https://github.com/C3Techie/ticket-webapp-react.git)
  - [ticket-webapp-vue](https://github.com/C3Techie/ticket-webapp-vue.git)

---

## Notes

- The layout, design, and logic are consistent across all framework versions as per the task requirements.
- Authentication and ticket data are simulated using JSON files in `storage/` (mimics localStorage).
- All protected routes require a valid session token (`ticketapp_session`).
- Status color rules: `open` (green), `in_progress` (amber), `closed` (gray).

---
