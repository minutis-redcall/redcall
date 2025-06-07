'use strict';

const {CloudTasksClient} = require('@google-cloud/tasks');
const client = new CloudTasksClient();

const PROJECT_ID = process.env.PROJECT_ID || null;
const TASK_QUEUE_LOCATION = process.env.TASK_QUEUE_LOCATION || null;
const TASK_QUEUE_NAME = process.env.TASK_QUEUE_NAME || null;

console.info("setting up cloud function", JSON.stringify({
    PROJECT_ID: PROJECT_ID,
    TASK_QUEUE_LOCATION: TASK_QUEUE_LOCATION,
    TASK_QUEUE_NAME: TASK_QUEUE_NAME
}));

const parent = client.queuePath(PROJECT_ID, TASK_QUEUE_LOCATION, TASK_QUEUE_NAME);

//http request : https://expressjs.com/en/4x/api.html#req
// check here for async usage : https://thecloudfunction.com/blog/firebase-cloud-functions-and-cloud-tasks/
//https://medium.com/@rogiervandenberg/google-cloud-task-queues-on-gcp-with-google-cloud-functions-22eb80fe34ba
//https://www.npmjs.com/package/@google-cloud/tasks#using-the-client-library

// Google Permissions : https://cloud.google.com/tasks/docs/reference/rest/v2/projects.locations.queues.tasks#appenginehttprequest
//
exports.¤CloudFunctioName¤ = async (req, res) => {

    console.info("processing message", JSON.stringify({
        PROJECT_ID: PROJECT_ID,
        TASK_QUEUE_LOCATION: TASK_QUEUE_LOCATION,
        TASK_QUEUE_NAME: TASK_QUEUE_NAME,
        HTTP_REQUEST: {
            query: req.query,
            body: req.body,
            headers: req.headers
        }
    }));

    try {
        // Construct task payload
        const bodyForAppEngine = {
            WebhookRequest: {
                absoluteUri: req.protocol + '://' + req.hostname + '/webHooksToTasksSMSResponse' + req.originalUrl,
                relativeUri: req.originalUrl,
                queryParams: req.query,
                headers: req.headers,
                body: req.body,
                rawBody: req.rawBody,
                origin: TASK_QUEUE_NAME
            }
        };

        const jsonBodyForAppEngine = Buffer.from(JSON.stringify(bodyForAppEngine));

        const task = {
            appEngineHttpRequest: {
                httpMethod: "POST",
                relativeUri: '/task/webhook',
                body: jsonBodyForAppEngine,
                headers: {
                    "Content-Type": "application/json"
                }
            }
        };

        const request = {parent, task};

        console.info("calling create task");
        const response = await client.createTask(request);
        console.info("Task posted", JSON.stringify(response));
        res.status(200).send();
    } catch (error) {
        console.error("Error creating task:", error);
        res.status(500).send("Error creating task");
    }
};
