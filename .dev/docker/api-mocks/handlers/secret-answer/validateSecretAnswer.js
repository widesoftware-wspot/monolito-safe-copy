
module.exports = async function (req, res) {
    const hasError = req.headers['x-error-code'] !== undefined;
    if (hasError){
        try {
            const errorCode = req.headers['x-error-code'];
            if (errorCode == 423 || errorCode == 403){
                res
                    .status(errorCode)
                    .header('Content-Language', 'pt_BR')
                    .json({"message": "An error has been ocurred", "retry_attempts": 3});
            }else {
                res
                    .status(errorCode)
                    .header('Content-Language', 'pt_BR')
                    .json({"message": "An error has been ocurred"});
            }
        }catch (e) {
            res
                .status(500)
                .header('Content-Language', 'pt_BR')
                .json({"message": "An error has been ocurred"});
        }
    }else {
        res
            .status(200)
            .header('Content-Language', 'pt_BR')
            .json({"message": "The answer was validated"});
    }
};