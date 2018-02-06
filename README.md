# wi-api-php-getting-started

# Clone and Deploy to Heroku

Install the Heroku CLI
https://devcenter.heroku.com/articles/heroku-cli

$ git clone git@github.com:becwatson/api-php-getting-started.git # or clone your own fork

$ cd api-php-getting-started

$ heroku create

$ git push heroku master

$ heroku open


# Updating App

Update the code files, commit changes to your local git clone then push local changes to heroku:

$ git commit -a -m "Updated files"

$ git push heroku master

$ heroku open


# API access

Apply for API key via wiapi@elit and set environment variables.

For heroku this can be done as follows:

$ heroku config:set WI_ACCOUNT_ID="<account_id>"

$ heroku config:set WI_ACCOUNT_TOKEN="<account_token>"

To unset variables use:

$ heroku config:unset WI_ACCOUNT_ID

$ heroku config:unset WI_ACCOUNT_TOKEN
