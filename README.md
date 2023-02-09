# Document for Wordpress Docker
## Step 1: Install Docker.

[Install Docker](https://docs.docker.com/get-docker/). Read more about docker [here](https://docs.docker.com/compose/overview/#compose-documentation).

## Step 2: Create the new MySQL database.
Install phpmyadmin and mySQL via docker

#### 1. Clone source mysql
[docker-mysql](https://github.com/chrisnguyen96/docker-mysql)

#### 2. Clone the file `.env.sample` to `.env` 
In the same directory and update the configuration for project.

#### 3. Update the configuration for project
```bash=
DB_ROOT_PASSWORD=123456 #Root password for database
DB_PORT=3306 
ADMIN_PORT=8080 #You can access phpmyadmin via localhost:8080
```
#### 4. Use terminal or visual code terminal access into the source folder
```bash=
# Find the folder store the source code
$ cd mysql-docker

#Run the docker-compose
$ docker-compose up -d
```

#### 5. Access phpmyadmin to create database 
:::success
https://localhost:8080
user : root
pass : 123456
:::
*Note: You can change the account via `.env` file*

#### 6.Create a new database or import database exist
Go to Wp Admin and Migrate DB export DB from live site

##### Note: If it not work try `docker-compose down` and `docker-compose up -d` again.

## Step 3: Clone the wordpress project source

#### 1. Clone the file `.env.sample` to `.env` 
In the same directory and update the configuration for project.

#### 2. Edit the `.env` file
```bash=
#Change the project_id. Ex: wp-project_name
PROJECT_ID=wp_data

#Change the localhost port. Another project just change this number. Access the http://localhost:69
PORT=69

#Active debug mode for wordpress logs. Can be true/false.
DEBUG_MODE=true

# Change the project-name same with theme of project. Only use for MAC and LINUX.
DIR_SASS=wp-content/themes/project-name/assets/scss
DIR_CSS=wp-content/themes/project-name/assets/css

# Change the access of database.
DB_HOST=mysql
DB_NAME=wp_data #change this into database created above.
DB_USER=root
DB_PASS=123456
DB_TABLE_PREFIX=wp_
```
#### 3. Use terminal or visual code terminal access into the source folder
```bash=
# Find the folder store the source code
$ cd project-name

#Run the docker-compose
$ docker-compose up -d
```
#### 4. Access the project in localhost
:::success
http://localhost:69
:::

#### 5. On the development start the gulp for SCSS generator
:::success
#Install the npm package
$ npm install

#Start gulp
$ gulp watch
:::


## Tip
```bash=
# Stop and remove docker container
$ docker-compose down

# List all image
$ docker image ls

# Clean image
$ docker image rm <name-of-image>

#Dev note

```
