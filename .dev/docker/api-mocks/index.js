const express = require('express')
const bodyParser = require('body-parser')
const app = express()
const port = 3000

const consentsListHandler = require('./handlers/consentsListHandler')
const signedConsentsRevokeHadler = require('./handlers/signedConsentsRevokeHandler')
const surveyHandler = require('./handlers/surveyHandler')
const createSecretAnswer = require('./handlers/secret-answer/createSecretAnswer')
const getSecretQuestions = require('./handlers/secret-answer/getSecretQuestion')
const validateSecretAnswer = require('./handlers/secret-answer/validateSecretAnswer')
const getSecretQuestionAnswered = require('./handlers/secret-answer/getSecretQuestionAnswered')

// parse application/x-www-form-urlencoded
app.use(bodyParser.urlencoded({ extended: false }))
// parse application/json
app.use(bodyParser.json())


app.get('/v1/clients/:client_id/consent', consentsListHandler)
app.delete('/v1/guests/:guest_id/signed-consents', signedConsentsRevokeHadler)

app.get('/api/v1/guests/:guest_id/client/:client_id', surveyHandler)

app.post('/secret-answer/api/v1/guests/:guest_id/sa', validateSecretAnswer)
app.get('/secret-answer/api/v1/guests/:guest_id/question', getSecretQuestionAnswered)
app.post('/secret-answer/api/v1/secret-answers', createSecretAnswer)
app.get('/secret-answer/api/v1/secret-questions', getSecretQuestions)

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

app.listen(port, () => {
    console.log(`Mock Apis app listening at http://localhost:${port}`)
})
