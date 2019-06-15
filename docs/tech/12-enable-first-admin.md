
## Enable your first admin

**Objective**:
When you will sign up your first user, you indeed won't be able to
ask the administrator to enable your account.

First, create your account using the registration form, whatever the
environment.

### On the development environment

Inside the container, you just need to run the following command:

```
php bin/console user:verify <your registration email>
php bin/console user:trust <your registration email>
php bin/console user:admin <your registration email>
```

### On the production environment

1. Run the [development environment](00-development.md) in order to generate the database schema

2. Enable your public IP address in your Cloud SQL instance (see [this related doc](07-google-cloud-sql.md))

3. In the `.env` file of the [symfony](symfony/) directory, backup and change `DATABASE_URL` environment variable to point to your Cloud SQL db:

```
DATABASE_URL=mysql://root:<your root password>@<your instance public ip>:3306/redcall_preprod
```

4. Inside the container, run the following command:

```
php bin/console user:verify <your registration email>
php bin/console user:trust <your registration email>
php bin/console user:admin <your registration email>
```

5. Disable your public IP address in your Cloud SQL instance.

6. Restore your `DATABASE_URL` variable in `.env`.

[Go back](../../README.md)
