<?php

namespace Wideti\AdminBundle\Form;

use Facebook\Facebook;
use GuzzleHttp\Exception\ClientException;
use League\Period\Period;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Wideti\AdminBundle\Form\ConfigType;
use Wideti\DomainBundle\Entity\ClientConfiguration;
use Wideti\DomainBundle\Helpers\ValidationHelper;
use Wideti\DomainBundle\Service\Configuration\ConfigurationServiceImp;
use Wideti\DomainBundle\Service\Configuration\Dto\ConfigurationDto;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsAware;
use Wideti\DomainBundle\Service\Group\GroupServiceAware;
use Wideti\DomainBundle\Service\Module\ModuleAware;
use Wideti\WebFrameworkBundle\Aware\MongoAware;
use Wideti\WebFrameworkBundle\Aware\SessionAware;

class SetupType extends AbstractType
{
	use SessionAware;
	use ModuleAware;
	use MongoAware;
	use GroupServiceAware;
	use CustomFieldsAware;

	protected $facebookAppId;
	protected $facebookAppSecret;
	protected $fb;
	/**
	 * @var ConfigurationServiceImp
	 */
	private $configurationService;

	/**
	 * SetupType constructor.
	 * @param $facebookAppId
	 * @param $facebookAppSecret
	 * @param ConfigurationServiceImp $configurationService
	 * @throws \Facebook\Exceptions\FacebookSDKException
	 */
	public function __construct($facebookAppId, $facebookAppSecret, ConfigurationServiceImp $configurationService)
	{
		$this->facebookAppId     = $facebookAppId;
		$this->facebookAppSecret = $facebookAppSecret;

		$this->fb = new Facebook([
			'app_id' => $this->facebookAppId,
			'app_secret' => $this->facebookAppSecret,
			'default_graph_version' => 'v2.5'
		]);
		$this->configurationService = $configurationService;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$groupId = $options['attr']['groupId'];
		$configs = $this->configurationService->getByGroupId($groupId);
		$isAgeRestrictionActivate   = $this->moduleService->modulePermission('age_restriction');

		/**
		 * @var ConfigurationDto $config
		 */
		foreach ($configs as $config) {
			if (in_array($config->getType(), ['file', 'timer', 'internal'])) {
				continue;
			}

            if ($config->getKey() == 'twitter_login') {
                continue;
            }

			if ($config->getKey() == 'age_restriction' && !$isAgeRestrictionActivate) {
                continue;
            }

			if (in_array($config->getKey(), ['router_mode'])) {
			    continue;
            }

			if ($config->getKey() == 'from_email') {
                $config->setType('email');
            }

			$builder->add(
				$config->getKey(),
				ConfigType::class,
				[
					'label'     => $config->getLabel(),
					'data'      => $config,
					'required'  => false
				]
			);
		}

		$builder->add('submit', SubmitType::class);
	}

	public function getBlockPrefix()
	{
		return 'setup';
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'constraints' => new Callback([$this, 'checkEmptyFields'])
		]);
	}

	public function checkEmptyFields($data, ExecutionContextInterface $context)
	{
		$confirmationEmailValue         = $data['confirmation_email']->getValue();
		$blockPerTimeOrAccessValidity   = $this->groupService->checkModuleIsActive('blockPerTimeOrAccessValidity');
		$accessCodeModule               = $this->moduleService->checkModuleIsActive('access_code');
		$businessHoursModule            = $this->moduleService->checkModuleIsActive('business_hours');
		$emailField                     = $this->customFieldsService->getFieldByNameType('email');
		$phoneField                     = ($this->customFieldsService->getFieldByNameType('phone') ?: $this->customFieldsService->getFieldByNameType('mobile'));

		if ($accessCodeModule && $confirmationEmailValue == 1) {
			$context
				->buildViolation(
					'Não é possível ativar a opção de [Confirmação de cadastro por e-mail] com o módulo Código de
                    Acesso ativo.'
				)
				->atPath('setup_enable_confirmation')
				->addViolation();
		}

		if ($businessHoursModule && $confirmationEmailValue == 1) {
			$context
				->buildViolation(
					'Não é possível ativar a opção de [Confirmação de cadastro por e-mail] com
                    o módulo Horário de Funcionamento ativo.'
				)
				->atPath('setup_enable_confirmation')
				->addViolation();
		}

		// if ($blockPerTimeOrAccessValidity && $confirmationEmailValue == 1) {
		// 	$context
		// 		->buildViolation(
		// 			'Não é possível ativar a opção de [Confirmação de cadastro por e-mail] com
        //             o módulo Bloqueio por tempo / Validade de acesso (Grupo de visitantes) ativo.'
		// 		)
		// 		->atPath('setup_enable_confirmation')
		// 		->addViolation();
		// }

		if (
			$this->moduleService->checkModuleIsActive('access_code') !== true) {
			if (isset($data['confirmation_email'])) {
				if ($confirmationEmailValue == 1) {
					if (!$emailField) {
						$context
							->buildViolation(
								"Ativação de 'Confirmação de cadastro via E-mail' não disponível, pois você não possui
                                o campo E-MAIL no formulário de cadastro."
							)
							->atPath('enable_welcome_sms')
							->addViolation();
					}

					if ($emailField) {
						foreach ($emailField->getValidations() as $validations) {
							$type    = $validations['type'];
							$value   = $validations['value'];

							if ($type == 'required' && $value == false) {
								$context
									->buildViolation(
										"Ativação de 'Confirmação de cadastro via E-mail' não disponível, pois o campo
                                         E-MAIL não é obrigatório no formulário de cadastro."
									)
									->atPath('confirmation_email')
									->addViolation();
							}
						}
					}

					if ($data['confirmation_email_limit_time']->getValue() === null) {
						$context
							->buildViolation(
								'O preenchimento do campo [Tempo limite para confirmação] é obrigatório.'
							)
							->atPath('confirmation_email')
							->addViolation();
					}

					if ($data['confirmation_email_block_time']->getValue() === null) {
						$context
							->buildViolation(
								'O preenchimento do campo [Tempo de bloqueio] é obrigatório.'
							)
							->atPath('confirmation_email')
							->addViolation();
					}
				}

				if ($confirmationEmailValue === true &&
					$data['confirmation_email_limit_time']->getValue()
				) {
					$value = $data['confirmation_email_limit_time']->getValue();
					$value = strtoupper($value);
					$value = str_replace(['D', 'H', 'M', 'S'], ['DAY', 'HOUR', 'MINUTE', 'SECOND'], $value);

					try {
						new Period(new \DateTime(), $value);
					} catch (\Exception $ex) {
						return $context
							->buildViolation(
								'Informe um valor válido no campo [Tempo limite para confirmação], ex: 4d 12h 5m'
							)
							->atPath('confirmation_email_limit_time')
							->addViolation();
					}
				}

				if ($confirmationEmailValue === true &&
					$data['confirmation_email_block_time']->getValue()
				) {
					$value = $data['confirmation_email_block_time']->getValue();
					$value = strtoupper($value);
					$value = str_replace(['D', 'H', 'M', 'S'], ['DAY', 'HOUR', 'MINUTE', 'SECOND'], $value);

					try {
						new Period(new \DateTime(), $value);
					} catch (\Exception $ex) {
						return $context
							->buildViolation(
								'Informe um valor válido no campo [Tempo de bloqueio], ex: 4d 12h 5m'
							)
							->atPath('confirmation_email_block_time')
							->addViolation();
					}
				}
			}
		}

		if (isset($data['confirmation_sms'])) {
			if ($data['confirmation_sms']->getValue() == 1 && !$phoneField) {
				if (!$phoneField) {
					$context
						->buildViolation(
							"Ativação de 'Confirmação de cadastro via SMS' não disponível, pois você não possui o campo
                             TELEFONE/CELULAR no formulário de cadastro."
						)
						->atPath('confirmation_sms')
						->addViolation();
				}

				if ($phoneField) {
					foreach ($phoneField->getValidations() as $validations) {
						$type    = $validations['type'];
						$value   = $validations['value'];

						if ($type == 'required' && $value == false) {
							$context
								->buildViolation(
									"Ativação de 'Confirmação de cadastro via SMS' não disponível, pois o campo TELEFONE/CELULAR
                                    não é obrigatório no formulário de cadastro."
								)
								->atPath('confirmation_sms')
								->addViolation();
						}
					}
				}
			}

			if ($data['confirmation_sms']->getValue() == 1 && $confirmationEmailValue == 1) {
				$context
					->buildViolation(
						'Apenas uma opção de confirmação de cadastro deverá ser selecionada. Via SMS ou via E-mail.'
					)
					->atPath('confirmation_sms')
					->addViolation();
			}

			if (strpos($data['content_confirmation_sms_pt']->getValue(), '{ codigo }') == false ||
				strpos($data['content_confirmation_sms_en']->getValue(), '{ codigo }') == false ||
				strpos($data['content_confirmation_sms_es']->getValue(), '{ codigo }') == false) {
				$context
					->buildViolation(
						'A variável { codigo } é obrigatória no conteúdo da mensagem enviada via SMS.'
					)
					->atPath('content_confirmation_sms_pt')
					->addViolation();
			}
		}

		if ($data['enable_welcome_sms']->getValue() == 1) {
			if (!$phoneField) {
				$context
					->buildViolation(
						"Ativação de 'Credenciais via SMS' não disponível, pois você não possui o campo TELEFONE no
                        formulário de cadastro."
					)
					->atPath('enable_welcome_sms')
					->addViolation();
			}

			if ($phoneField) {
				foreach ($phoneField->getValidations() as $validations) {
					$type    = $validations['type'];
					$value   = $validations['value'];

					if ($type == 'required' && $value == false) {
						$context
							->buildViolation(
								"Ativação de 'Credenciais via SMS' não disponível, pois o campo TELEFONE/CELULAR não é obrigatório
                                 no formulário de cadastro."
							)
							->atPath('enable_welcome_sms')
							->addViolation();
					}
				}
			}
		}

		if ($data['authorize_email']->getValue() == 1) {
			if (!$emailField) {
				$context
					->buildViolation(
						"Ativação de 'Solicitar opt-in dos visitantes' não disponível, pois você não possui o campo E-MAIL
                    no formulário de cadastro."
					)
					->atPath('authorize_email')
					->addViolation();
			}

			if ($emailField) {
				foreach ($emailField->getValidations() as $validations) {
					$type    = $validations['type'];
					$value   = $validations['value'];

					if ($type == 'required' && $value == false) {
						$context
							->buildViolation(
								"Ativação de 'Solicitar opt-in dos visitantes' não disponível, pois o campo E-MAIL não é
                                 obrigatório no formulário de cadastro."
							)
							->atPath('authorize_email')
							->addViolation();
					}
				}
			}
		}

		if ($data['request_optin']->getValue() == 1) {
			if (!$emailField) {
				$context
					->buildViolation(
						"Ativação de 'Solicitar opt-in dos visitantes' não disponível, pois você não possui o campo E-MAIL
                    no formulário de cadastro."
					)
					->atPath('authorize_email')
					->addViolation();
			}

			if ($emailField) {
				foreach ($emailField->getValidations() as $validations) {
					$type    = $validations['type'];
					$value   = $validations['value'];

					if ($type == 'required' && $value == false) {
						$context
							->buildViolation(
								"Ativação de 'Solicitar opt-in dos visitantes' não disponível, pois o campo E-MAIL não é
                                 obrigatório no formulário de cadastro."
							)
							->atPath('authorize_email')
							->addViolation();
					}
				}
			}
		}

		$this->checkPartnerNameSize($data, $context);

		$this->checkSpecialCharacter($data, $context);

		$context = $this->validateFacebookShareOption($context, $data);
		$context = $this->validateFacebookLikeOption($context, $data);
		$context = $this->validateFacebookShareHashtagOption($context, $data);

		$redirectUrl = $data['redirect_url']->getValue();

		if (substr($redirectUrl, 0, 5) != 'http:') {
			if (substr($redirectUrl, 0, 5) != 'https') {
				$context
					->buildViolation(
						'Informe uma URL de redirecionamento no formato válido, ex: http://www.google.com'
					)
					->atPath('setup_redirect_url')
					->addViolation();
			}
		}
	}

    public function checkPartnerNameSize($data, ExecutionContextInterface $context)
    {
        $partnerName = $data['partner_name']->getValue();
        $fieldPt = $data['content_confirmation_sms_pt']->getValue();
        $fieldEn = $data['content_confirmation_sms_en']->getValue();
        $fieldEs = $data['content_confirmation_sms_es']->getValue();

        $lengthPt = strpos($fieldPt, 'nome_da_empresa') &&  strlen($partnerName) > 19 ? strlen($partnerName) - 19 : 0;
        $lengthEn = strpos($fieldEn, 'nome_da_empresa') &&  strlen($partnerName) > 19 ? strlen($partnerName) - 19 : 0;
        $lengthEs = strpos($fieldEs, 'nome_da_empresa') &&  strlen($partnerName) > 19 ? strlen($partnerName) - 19 : 0;

        $lenPt = strlen($fieldPt);
        $lenEn = strlen($fieldEn);
        $lenEs = strlen($fieldEs);

        $enableSms = $data['confirmation_sms']->getValue();

        if ($enableSms) {
            if ($lenPt + $lengthPt > 160 || $lenEn + $lengthEn > 160
                || $lenEs + $lengthEs > 160
            ) {
                $context
                    ->buildViolation(
                        'O Nome da Empresa é muito grande, informe um menor.'
                    )
                    ->atPath('nome_empresa_grande')
                    ->addViolation();
            }
        }
    }

	/**
	 * @param ExecutionContextInterface $context
	 * @param array $fields
	 * @return ExecutionContextInterface
	 */
	private function validateFacebookShareHashtagOption(ExecutionContextInterface $context, array $fields)
	{
		/** @var Item $facebookHashtag */
		$facebookHashtag = isset($fields['facebook_share_hashtag']) ? $fields['facebook_share_hashtag'] : null;

		if ($facebookHashtag && empty($facebookHashtag->getValue()) ) {
			return $context;
		}

		if ($facebookHashtag && !$this->checkIfIsValidFacebookHashtag($facebookHashtag->getValue())) {
			$context
				->buildViolation(
					"Hashtag {$facebookHashtag->getValue()} não é válida."
				)
				->atPath('facebook_share_hashtag')
				->addViolation();
		}

		return $context;
	}

	/**
	 * @param ExecutionContextInterface $context
	 * @param array $fields
	 * @return ExecutionContextInterface
	 */
	private function validateFacebookShareOption(ExecutionContextInterface $context, array $fields)
	{
		/** @var Item $facebookShareItem */
		$facebookShareItem = isset($fields['facebook_share']) ? $fields['facebook_share'] : null;

		/** @var Item $facebookShareUrlItem */
		$facebookShareUrlItem = isset($fields['facebook_share_url']) ? $fields['facebook_share_url'] : null;

		if ($facebookShareItem && $facebookShareItem->getValue() == true) {
			if (!$facebookShareUrlItem || empty($facebookShareUrlItem->getValue())) {
				$context
					->buildViolation(
						'Para ativar o compartilhamento é necessário adicionar uma URL para ser compartilhada.'
					)
					->atPath('facebook_share_url')
					->addViolation();
			}
		}

		return $context;
	}

	/**
	 * @param ExecutionContextInterface $context
	 * @param array $fields
	 * @return ExecutionContextInterface
	 */
	private function validateFacebookLikeOption(ExecutionContextInterface $context, array $fields)
	{
		/** @var Item $facebookLikeItem */
		$facebookLikeItem = isset($fields['facebook_like']) ? $fields['facebook_like'] : null;

		/** @var Item $facebookLikeUrlItem */
		$facebookLikeUrlItem = isset($fields['facebook_like_url']) ? $fields['facebook_like_url'] : null;

		$isActiveLike = ($facebookLikeItem && $facebookLikeItem->getValue() == true);

		if ($isActiveLike) {
			if (!$facebookLikeUrlItem || empty($facebookLikeUrlItem->getValue())) {
				$context
					->buildViolation(
						'Para ativar o Like é necessário adicionar uma página válida do facebook'
					)
					->atPath('setup_redirect_url')
					->addViolation();
			}
		}

		$facebookPageUrl = $facebookLikeUrlItem->getValue();
		if ($isActiveLike && !$this->checkIfFacebookPageIsValid($facebookPageUrl)) {
			$context
				->buildViolation(
					"O endereço {$facebookPageUrl} não é um endereço válido de uma página no facebook"
				)
				->atPath('setup_redirect_url')
				->addViolation();
		}

		return $context;
	}

	/**
	 * @param $pageUrl
	 * @return bool
	 */
	private function checkIfFacebookPageIsValid($pageUrl)
	{
		$client = new \GuzzleHttp\Client();

		if(!preg_match('/(facebook.com\/)([a-zA-Z0-9-_]{2,})$/i', $pageUrl)) {
			return false;
		}

		try {
			$client->head($pageUrl);
			return true;
		} catch (ClientException $exception) {
			return false;
		} catch (\Exception $ex) {
		    return true;
        }
	}

	/**
	 * @param $hashtag
	 * @return bool
	 */
	private function checkIfIsValidFacebookHashtag($hashtag)
	{
		return (boolean) preg_match('/^(#)([a-zA-Z0-9]{1,})$/i', $hashtag);
	}

    private function checkSpecialCharacter($data, $context)
    {
        $smsPtIsValid       = $this->validateSpecialCharacterOnSMS($data['content_confirmation_sms_pt']->getValue());
        $smsEnIsValid       = $this->validateSpecialCharacterOnSMS($data['content_confirmation_sms_en']->getValue());
        $smsEsIsValid       = $this->validateSpecialCharacterOnSMS($data['content_confirmation_sms_es']->getValue());

        if (!$smsPtIsValid || !$smsEnIsValid || !$smsEsIsValid) {
            $context
                ->buildViolation(
                    'Caracteres especiais não são aceitos'
                )
                ->atPath('caracteres_especiais')
                ->addViolation();
        }
    }

    public function validateSpecialCharacterOnSMS($value)
    {
        return ValidationHelper::validateSpecialCharacterSMS($value);
    }
}
