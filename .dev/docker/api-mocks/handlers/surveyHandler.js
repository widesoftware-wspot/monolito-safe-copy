
module.exports = async function (req, res) {
    res
        .status(200)
        .header('Content-Language', 'en_US')
        .json(mustShowSurvey());
};

const mustShowSurvey = () => ({
    "show_survey": true,
    "survey_id": 3214,
})