
# Module CSV importer

How to test 

Replace the "MODULE_MAIL_STATUS_MAIL" with youremail@domain.com on .env file.

Postman API collection is uploaded on /Docs folder

URL : http://localhost:8000/api/modules-file-up-api

POST Request as form-data

file {SELECT_FILE}

Response : {"success":true,"message":"CSV File successfully uploaded : File ID 15","file":{}} 

# Sample CSV data

Sample CSV is also available on /Docs/SampleCsv folder

MODULE_DATA_ERROR.csv
MODULE_DATA_HEADER_ERROR.csv
MODULE_DATA_VALID.csv

# Queue Execution

For testing purpose QUEUE_CONNECTION is in sync mode.

We can change to QUEUE_CONNECTION=database to make the queue function in background.