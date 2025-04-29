
module.exports = async function (req, res) {

    const hasError = req.headers['x-error-code'] !== undefined;
    const guestId = Number(req.params.guest_id)
    if (hasError){
        try {
            const errorCode = req.headers['x-error-code'];
            res
                .status(errorCode)
                .header('Content-Language', 'pt_BR')
                .json({"msg": "An error has been ocurred"});
        }catch (e) {
            res
                .status(500)
                .header('Content-Language', 'pt_BR')
                .json({"msg": "An error has been ocurred"});
        }
    }else {
        const language = req.query.language;
        if (language == 'es'){
            res
                .status(200)
                .header('Content-Language', 'pt_BR')
                .json({"spot_id": 1, "guest_id": guestId, "question_id": 1, "question": "¿Cuál es tu palabra secreta?"});
        }else if (language == 'en'){
            res
                .status(200)
                .header('Content-Language', 'pt_BR')
                .json({"spot_id": 1, "guest_id": guestId, "question_id": 1, "question": "What's your secret word?"});
        }else {
            res
                .status(200)
                .header('Content-Language', 'pt_BR')
                .json({"spot_id": 1, "guest_id": guestId, "question_id": 1, "question": "Qual é a sua palavra secreta?"});
        }
    }
};