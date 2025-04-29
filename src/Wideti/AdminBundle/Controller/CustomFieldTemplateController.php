<?php

namespace Wideti\AdminBundle\Controller;

use Wideti\DomainBundle\Service\Module\ModuleAware;

use Symfony\Component\HttpFoundation\Request;

use Wideti\WebFrameworkBundle\Aware\EntityManagerAware;
use Wideti\WebFrameworkBundle\Aware\FlashMessageAware;
use Wideti\WebFrameworkBundle\Aware\SecurityAware;
use Wideti\WebFrameworkBundle\Aware\TwigAware;
use Wideti\DomainBundle\Helpers\Controller\AdminControllerHelper;
use Wideti\DomainBundle\Entity\CustomFieldTemplate;
use Symfony\Component\HttpFoundation\JsonResponse;
use Wideti\DomainBundle\Service\CustomFields\CustomFieldsService;
use Wideti\AdminBundle\Form\CustomFieldTemplateType;

class CustomFieldTemplateController
{
    use EntityManagerAware;
    use TwigAware;
    use SecurityAware;
    use FlashMessageAware;
    use ModuleAware;

    /**
     * @var AdminControllerHelper
     */
    private $controllerHelper;

    /**
     * @var CustomFieldsService
     */
    private $customFieldService;

    /**
     * CustomFieldTemplateController constructor.
     * @param ConfigurationService $configurationService
     * @param AdminControllerHelper $controllerHelper
     * @param CacheServiceImp $cacheService
     * @param AnalyticsService $analyticsService
     * @param CustomFieldsService $customFieldService
     */
    public function __construct(
        AdminControllerHelper $controllerHelper,
        CustomFieldsService $customFieldService
    ) {
        $this->controllerHelper     = $controllerHelper;
        $this->customFieldService = $customFieldService;
        $this->fieldTypes = array(
            'text' => 'Campo de texto',
            'date' => 'Data',
            'choice' => 'Múltipla escolha',
            'multiple_choice' => 'Caixa de seleção',
        );
    }
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function newAction(Request $request)
    {
        $client = $this->getLoggedClient();
        $options = ['fieldTypes' => $this->fieldTypes];
        $options['attr']['actionForm'] = 'create';
        $options['attr']['clientDomain'] = $client->getDomain();

        $entity = new CustomFieldTemplate();

        $form   = $this->controllerHelper->createForm(CustomFieldTemplateType::class, $entity, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $content = $request->request->all()["wspot_custom_field_template"];
            try {
                $this->customFieldService->createCustomFieldFromForm($entity, $content);
            } catch (\Exception $ex) {
                $this->setFlashMessage("notice", "Ocorreu um erro, verifique se definiu o texto do campo corretamente, não pode haver dois campos com o mesmo texto");
                return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('custom_fields_index'));
            }
            $this->setCreatedFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('custom_fields_index'));
        }

        $formView = $form->createView();

        return $this->render(
            'AdminBundle:CustomFields:form.html.twig',
            [
                'entity'                    => $entity,
                'form'                      => $formView,
                'client'                    => $client,
                'actionForm'                => 'create'
            ]
        );
    }

    /**
     * @param Request $request
     * @param CustomFieldTemplate $customFieldTemplate
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function editAction(Request $request, CustomFieldTemplate $customFieldTemplate) {
        $client = $this->getLoggedClient();
        $clientDomain = $client->getDomain();
        $fieldInUse = $this->customFieldService->getFieldByNameType($customFieldTemplate->getIdentifier());
        if ($fieldInUse) {
            $this->setFlashMessage("notice", "Não é possível alterar campo em uso");
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('custom_fields_index'));
        }
        $options = [];
        $options = ['fieldTypes' => $this->fieldTypes];
        $options['attr']['clientDomain'] = $clientDomain;
        $options['attr']['actionForm'] = 'update';
        $options['entity'] = $customFieldTemplate;
        $existingChoices = null;
        $choices = $customFieldTemplate->getChoices();
        $visibleForClients = $customFieldTemplate->getVisibleForClients();


        if (!in_array($clientDomain, $visibleForClients)) {
            $this->setFailToGetFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('custom_fields_index'));
        }
        
        if ($choices) {
            $maxCount = max(count($choices['pt_br']), count($choices['en']), count($choices['es']));
            
            $pt_br_values = array_values($choices['pt_br']);
            $en_values = array_values($choices['en']);
            $es_values = array_values($choices['es']);
            
            $existingChoices = [];
            for ($i = 0; $i < $maxCount; $i++) {
                $pt_br_value = isset($pt_br_values[$i]) ? $pt_br_values[$i] : '';
                $en_value = isset($en_values[$i]) ? $en_values[$i] : '';
                $es_value = isset($es_values[$i]) ? $es_values[$i] : '';
            
                if ($pt_br_value !== '' && $en_value !== '' && $es_value !== '') {
                    $existingChoices[] = [
                        'pt_br' => $pt_br_value,
                        'en' => $en_value,
                        'es' => $es_value
                    ];
                }
            }
            
            $options['existing_choices'] = $existingChoices;
        }
        
        $form   = $this->controllerHelper->createForm(CustomFieldTemplateType::class, $customFieldTemplate, $options);
        $form->handleRequest($request);


        if ($form->isSubmitted()) {
            $content = $request->request->all()["wspot_custom_field_template"];
            $this->customFieldService->updateCustomFieldFromForm($customFieldTemplate, $content);
            $this->setUpdatedFlashMessage();
            return $this->controllerHelper->redirect($this->controllerHelper->generateUrl('custom_fields_index'));
        }

        $formView = $form->createView();

        return $this->render(
            'AdminBundle:CustomFields:form.html.twig',
            [
                'entity'                    => $customFieldTemplate,
                'form'                      => $formView,
                'client'                    => $client,
                'actionForm'                => 'update'
            ]
        );
    }

    /**
     * @param Request $request
     * @param CustomFieldTemplate $customFieldTemplate
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function deleteAction(Request $request, CustomFieldTemplate $customFieldTemplate) {
        $client = $this->getLoggedClient();
        $client = $this->getLoggedClient();
        $clientDomain = $client->getDomain();
        $visibleForClients = $customFieldTemplate->getVisibleForClients();

        if (!in_array($clientDomain, $visibleForClients)) {
            return new JsonResponse(['message' => "Exclusão não permitida"], 400);
        }

        try {
            $this->customFieldService->delete($customFieldTemplate);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }

        return new JsonResponse(['message' => 'Registro removido com sucesso']);
    }
}