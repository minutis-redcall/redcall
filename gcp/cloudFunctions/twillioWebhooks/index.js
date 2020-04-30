'use strict';

const {CloudTasksClient} = require('@google-cloud/tasks');
const client = new CloudTasksClient();

const PROJECT_ID          = process.env.PROJECT_ID          || null; //
const TASK_QUEUE_LOCATION = process.env.TASK_QUEUE_LOCATION || null; //europe-west1
const TASK_QUEUE_NAME     = process.env.TASK_QUEUE_NAME     || null; //messages-sms

console.info("setting up cloud function", JSON.stringify({PROJECT_ID:PROJECT_ID, TASK_QUEUE_LOCATION:TASK_QUEUE_LOCATION,TASK_QUEUE_NAME:TASK_QUEUE_NAME}));

const parent = client.queuePath(PROJECT_ID, TASK_QUEUE_LOCATION, TASK_QUEUE_NAME);
//http request : https://expressjs.com/en/4x/api.html#req
// check here for async usage : https://thecloudfunction.com/blog/firebase-cloud-functions-and-cloud-tasks/
//https://medium.com/@rogiervandenberg/google-cloud-task-queues-on-gcp-with-google-cloud-functions-22eb80fe34ba
//https://www.npmjs.com/package/@google-cloud/tasks#using-the-client-library

// Google Permissions : https://cloud.google.com/tasks/docs/reference/rest/v2/projects.locations.queues.tasks#appenginehttprequest
//
exports.webHooksToTasks = (req, res) => {

  console.info("processing message", JSON.stringify(
    {
      PROJECT_ID:PROJECT_ID,
      TASK_QUEUE_LOCATION:TASK_QUEUE_LOCATION,
      TASK_QUEUE_NAME:TASK_QUEUE_NAME,
      HTTP_REQUEST:
        {
          body:req.body,
          headers:req.headers,
          query:req.body
        }}));

  return new Promise((resolve, reject) => {

    const task = {
      appEngineHttpRequest: {
        httpMethod: "POST",
        relativeUri: '/connect',
        body: Buffer.from(JSON.stringify(req.body)).toString("base64"),
        headers: {
          "Content-Type": "application/json"
        }
      }
    };

    console.info("task created");

    //task.appEngineHttpRequest.headers = Buffer.from(req.headers).toString('base64');
    //task.appEngineHttpRequest.query   = Buffer.from(req.query  ).toString('base64');

    console.info("body set");
    const request = {parent, task};

    console.info("creating task");
    client.createTask(request).then((response) =>{
      console.info("Task posted", JSON.stringify(response));
      res.status(200).send(JSON.stringify(response));
      console.info("response sent");
      resolve();
    });

  }).catch((err) =>{
    console.error(err.message, JSON.stringify(err));
    res.status(500).send(JSON.stringify(err));
  });
};
