## Create the database schema

1. Go to `deploy/<your project>/dotenv` and check the following variables (change values accordingly):

```
DATABASE_HOST=10.202.64.3:3306
DATABASE_URL=mysql://redcall_prod:<my password>@$DATABASE_HOST/redcall_prod
GCP_PROJECT_NAME=covid-fight-redcall
GCP_BASTION_INSTANCE=redcall-bastion
```

2. Go to `deploy/<your project>/dotenv-migrate` and set the right DATABASE_URL
 
```
DATABASE_URL=mysql://redcall_prod:<my password>@$DATABASE_HOST/redcall_prod
```

3. Go to `deploy/<your project>/app.yaml` and change the VPC connector (change values accordingly):

```yaml
vpc_access_connector:
  name: "projects/covid-fight-redcall/locations/europe-west1/connectors/redcall-serverless-vpc"
```

4. You're ready to migrate the database

Edit the "migrate.sh" script and comment the following lines:

```
    GREENLIGHT=`wget -O- ${WEBSITE_URL}/deploy`
    if [[ "${GREENLIGHT}" != "0" ]]
    then
      echo "A communication has recently been triggered, cannot deploy before ${GREENLIGHT} seconds"
      cp deploying/.env symfony/.env
      rm -r deploying
      exit 1
    fi
```

Then run:

```
cd deploy
sh migrate.sh <your project>
```

You can uncomment the code you commented above.

## First deployment

1. Edit the "deploy.sh" script and comment the following lines:
   
```
   GREENLIGHT=`wget -O- ${WEBSITE_URL}/deploy`
   if [[ "${GREENLIGHT}" != "0" ]]
   then
     echo "A communication has recently been triggered, cannot deploy before ${GREENLIGHT} seconds"
     cp deploying/.env symfony/.env
     rm -r deploying
     exit 1
   fi
```

2. Run the following commands:

```
cd deploy
sh deploy.sh <your project>
```


## Enable your first admin

When you will sign up your first user, you indeed won't be able to
ask the administrator to enable your account.

1. first, create your account using the registration form, whatever the
environment.

2. use `start_sql.sh` to start and bind your SQL instance on your own local machine,
on port 3304. Set the "dotenv-migrate" variable at the bottom of your project's `.env`
(in the symfony directory). 

3. Finally, run the following commands:

```
php bin/console user:verify <your registration email>
php bin/console user:trust <your registration email>
php bin/console user:admin <your registration email>
```

php bin/console user:verify ninsuo@gmail.com
php bin/console user:trust ninsuo@gmail.com
php bin/console user:admin ninsuo@gmail.com

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
