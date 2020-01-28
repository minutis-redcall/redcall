# PasswordLoginBundle

This bundle provides simple, secure and user-friendly password login authentication.

Features protections against:

- CSRF in all forms (including login and logout)
- bruteforce kind of attacks (password reuse, dictionnaries, user enumeration...)
- host based attacks (host injection, redirect attacks)
- logged-in account take over (updating email or password requires current password)
- flooding (repeated password resets do not send too many emails)

But also provide some friendly features:

- user can enter a wrong password several times before a google recaptcha appears
- a admin-activated (so trusted) user that connects implicitely disables google recaptcha for its IP for 24h, can be useful on shared offices
- non-admin-activated accounts which have unverified emails are automatically removed after 24h
- with the given security.yaml, developers do not need to write down any other firewall rules

This bundle has originally been developed for a private backoffice, where anyone is able to subscribe, but only people known by the organization would be activated by admins. Thus, there are 3 types of users:

- Administrators (ROLE_ADMIN)
- Trusted users (ROLE_TRUSTED), they have been allowed to use the platform by admins
- Guest users (ROLE_USER), they registered and verified their email but can't access features
