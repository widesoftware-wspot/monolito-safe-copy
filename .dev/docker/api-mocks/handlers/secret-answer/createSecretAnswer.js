
module.exports = async function (req, res) {
    const hasError = req.headers['x-error-code'] !== undefined;
    if (hasError){
        try {
            const errorCode = req.headers['x-error-code'];
            res
                .status(errorCode)
                .header('Content-Language', 'pt_BR')
                .json({"error": "An error has been ocurred"});
        }catch (e) {
            res
                .status(500)
                .header('Content-Language', 'pt_BR')
                .json({"error": "An error has been ocurred"});
        }
    }else {
        const spotId = req.body.spot_id;
        const guestId = req.body.guest_id;
        const questionId = req.body.question_id;
        res
            .status(200)
            .header('Content-Language', 'pt_BR')
            .json({
                "id": 1,
                "spot_id": spotId,
                "guest_id": guestId,
                "question_id": questionId,
                "created_at": 1680619278,
            });
    }
};