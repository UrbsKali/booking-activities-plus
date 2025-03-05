# Booking Activities Plus

Booking Activities Plus is a WordPress plugin that extends the functionality of the Booking Activities plugin. It adds custom tabs and content to the user account page, allowing users to view their bookings, waiting lists, and passes.

## Installation

1. Download the plugin files.
2. Upload the plugin files to the `/wp-content/plugins/booking-activities-plus` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.

## Features

- Adds a custom booking tab to the user account page.
- Displays user bookings, waiting lists, and passes.
- Provides admin frontend view of user bookings, waiting lists, and passes.

## Usage

### Custom Booking Tab

The plugin adds a custom booking tab to the user account page. This tab displays the user's bookings, waiting lists, and passes using shortcodes.

#### Shortcodes

- `[bookingactivities_list columns="status,events,actions" user_id={user_id}]`: Displays the user's bookings.
- `[bookingactivities_waitinglist columns="events,actions" user_id={user_id}]`: Displays the user's waiting lists.
- `[bookingactivities_passes user_id={user_id}]`: Displays the user's passes.
- `[bookingactivities_certificate user_id={user_id}]`: Displays the user's certificates.
- `[bookingactivities_cancel_balance user_id={user_id}]`: Displays the user's remaining cancellations.

### Admin Frontend View

The plugin also adds information to the bottom of the user profile page on the admin frontend. This includes the user's bookings, waiting lists, and passes.

## Structure

### Controller

The `controller` folder contains the PHP files that handle the AJAX requests and shortcodes for the plugin. These files define the functions that process user actions, such as booking cancellations, fetching booking lists, and managing user certificates. The controllers ensure that the appropriate responses are sent back to the client-side, updating the user interface accordingly.

- `controller-waiting-list.php`: Handles AJAX requests related to the waiting list.
- `controller-user.php`: Manages user-related AJAX requests.
- `controller-shortcodes.php`: Defines the shortcodes used in the plugin.
- `controller-certificate.php`: Manages user certificates and related metadata.
- `controller-admin.php`: Handles admin-specific AJAX requests and settings.

### Functions

The `functions` folder contains various PHP files that define the core functionalities and utilities of the plugin. These files include helper functions, hooks, and filters that extend the capabilities of the Booking Activities plugin. The functions handle tasks such as managing waiting lists, validating bookings, and processing user passes.

- `functions-waiting-list.php`: Functions related to managing the waiting list.
- `functions-utils.php`: Utility functions used throughout the plugin.
- `functions-um.php`: Integrations with the Ultimate Member plugin.
- `functions-passes.php`: Functions for managing user passes.
- `functions-booking.php`: Functions for handling bookings and related validations.
- `functions-booking-system.php`: Functions for integrating with the booking system.

### Models

The `model` folder contains the PHP files that interact with the database. These files define the database schema, perform CRUD operations, and handle data retrieval and manipulation. The models ensure that the data is stored and retrieved efficiently and securely.

- `model-waiting-list.php`: Functions for managing the waiting list in the database.
- `model-install.php`: Functions for creating and dropping database tables during plugin installation and uninstallation.
- `model-global.php`: Global functions and constants related to database interactions.

### Views

The `view` folder contains the PHP files that generate the HTML output for the plugin's admin and frontend interfaces. These files define the layout and presentation of the data, ensuring that the user interface is intuitive and user-friendly.

- `view-settings.php`: Displays the settings page for the plugin.
- `view-booking-list.php`: Displays the booking list and waiting list in the admin interface.