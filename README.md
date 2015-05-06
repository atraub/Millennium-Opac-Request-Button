# Millennium Item Request Aggregation (MIRA)
Provides a simple, unified interface to intercept item requests, automatically choose a request source based on availibility, and execute a request for the user.

MIRA Consists of 4 components:
- **Link Interceptor**: detects request links on the search or record page, anaylizes availibility information, and either
removes the request link or generates a dialog when it is clicked
- **Availibility Checker**: Screen-scrapes from other Millenium catalogs, analyzing the data to determine where an item is
available
- **LDAP Authenticator**: Gathers credentials and validates them with the institution's authentication system, so they can be
safely passed to the chosen partner system
- **Request Generator**: Generates a server-side request to the given system for an item and processes the response

These components are all presented through a simple, user-friendly dialog, that allows the patron to request an item by
authenticating on a single screen, without ever leaving the original catalog page.
