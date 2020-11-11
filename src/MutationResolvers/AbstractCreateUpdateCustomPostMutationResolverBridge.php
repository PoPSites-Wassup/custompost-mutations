<?php

declare(strict_types=1);

namespace PoPSitesWassup\CustomPostMutations\MutationResolvers;

use PoP\Hooks\Facades\HooksAPIFacade;
use PoPSchema\CustomPosts\Types\Status;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoPSchema\CustomPosts\Facades\CustomPostTypeAPIFacade;
use PoP\ComponentModel\ModuleProcessors\DataloadingConstants;
use PoP\ComponentModel\Facades\ModuleProcessors\ModuleProcessorManagerFacade;
use PoP\ComponentModel\MutationResolvers\AbstractCRUDComponentMutationResolverBridge;

abstract class AbstractCreateUpdateCustomPostMutationResolverBridge extends \PoPSchema\CustomPostMutations\MutationResolvers\AbstractCreateUpdateCustomPostMutationResolverBridge
{
    /**
     * @param mixed $result_id Maybe an int, maybe a string
     */
    public function getSuccessString($result_id): ?string
    {
        $customPostTypeAPI = CustomPostTypeAPIFacade::getInstance();
        $status = $customPostTypeAPI->getStatus($result_id);
        if ($status == Status::PUBLISHED) {
            $success_string = sprintf(
                TranslationAPIFacade::getInstance()->__('<a href="%s" %s>Click here to view it</a>.', 'pop-application'),
                $customPostTypeAPI->getPermalink($result_id),
                getReloadurlLinkattrs()
            );
        } elseif ($status == Status::DRAFT) {
            $success_string = TranslationAPIFacade::getInstance()->__('The status is still “Draft”, so it won\'t be online.', 'pop-application');
        } elseif ($status == Status::PENDING) {
            $success_string = TranslationAPIFacade::getInstance()->__('Now waiting for approval from the admins.', 'pop-application');
        }

        return HooksAPIFacade::getInstance()->applyFilters('gd-createupdate-post:execute:successstring', $success_string, $result_id, $status);
    }

    public function getFormData(): array
    {
        $form_data = parent::getFormData();

        $moduleprocessor_manager = ModuleProcessorManagerFacade::getInstance();

        if (!$this->supportsTitle()) {
            unset($form_data[MutationInputProperties::TITLE]);
        }

        // Status: 2 possibilities:
        // - Moderate: then using the Draft/Pending/Publish Select, user cannot choose 'Publish' when creating a post
        // - No moderation: using the 'Keep as Draft' checkbox, completely omitting value 'Pending', post is either 'draft' or 'publish'
        if ($this->moderate()) {
            $form_data[MutationInputProperties::STATUS] = $moduleprocessor_manager->getProcessor([\PoP_Module_Processor_CreateUpdatePostSelectFormInputs::class, \PoP_Module_Processor_CreateUpdatePostSelectFormInputs::MODULE_FORMINPUT_CUP_STATUS])->getValue([\PoP_Module_Processor_CreateUpdatePostSelectFormInputs::class, \PoP_Module_Processor_CreateUpdatePostSelectFormInputs::MODULE_FORMINPUT_CUP_STATUS]);
        } else {
            $keepasdraft = $moduleprocessor_manager->getProcessor([\PoP_Module_Processor_CreateUpdatePostCheckboxFormInputs::class, \PoP_Module_Processor_CreateUpdatePostCheckboxFormInputs::MODULE_FORMINPUT_CUP_KEEPASDRAFT])->getValue([\PoP_Module_Processor_CreateUpdatePostCheckboxFormInputs::class, \PoP_Module_Processor_CreateUpdatePostCheckboxFormInputs::MODULE_FORMINPUT_CUP_KEEPASDRAFT]);
            $form_data[MutationInputProperties::STATUS] = $keepasdraft ? Status::DRAFT : Status::PUBLISHED;
        }

        if ($this->addReferences()) {
            $references = $moduleprocessor_manager->getProcessor([\PoP_Module_Processor_PostSelectableTypeaheadFormComponents::class, \PoP_Module_Processor_PostSelectableTypeaheadFormComponents::MODULE_FORMCOMPONENT_SELECTABLETYPEAHEAD_REFERENCES])->getValue([\PoP_Module_Processor_PostSelectableTypeaheadFormComponents::class, \PoP_Module_Processor_PostSelectableTypeaheadFormComponents::MODULE_FORMCOMPONENT_SELECTABLETYPEAHEAD_REFERENCES]);
            $form_data[MutationInputProperties::REFERENCES] = $references ?? array();
        }

        if (\PoP_ApplicationProcessors_Utils::addCategories()) {
            $topics = $moduleprocessor_manager->getProcessor([\PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::class, \PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::MODULE_FORMINPUT_CATEGORIES])->getValue([\PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::class, \PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::MODULE_FORMINPUT_CATEGORIES]);
            $form_data[MutationInputProperties::TOPICS] = $topics ?? array();
        }

        // Only if the Volunteering is enabled
        if (defined('POP_VOLUNTEERING_INITIALIZED')) {
            if (defined('POP_VOLUNTEERING_ROUTE_VOLUNTEER') && POP_VOLUNTEERING_ROUTE_VOLUNTEER) {
                if ($this->volunteer()) {
                    $form_data[MutationInputProperties::VOLUNTEERSNEEDED] = $moduleprocessor_manager->getProcessor([\GD_Custom_Module_Processor_SelectFormInputs::class, \GD_Custom_Module_Processor_SelectFormInputs::MODULE_FORMINPUT_VOLUNTEERSNEEDED_SELECT])->getValue([\GD_Custom_Module_Processor_SelectFormInputs::class, GD_Custom_Module_Processor_SelectFormInputs::MODULE_FORMINPUT_VOLUNTEERSNEEDED_SELECT]);
                }
            }
        }

        if (\PoP_ApplicationProcessors_Utils::addAppliesto()) {
            $appliesto = $moduleprocessor_manager->getProcessor([\PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::class, \PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::MODULE_FORMINPUT_APPLIESTO])->getValue([\PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::class, PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::MODULE_FORMINPUT_APPLIESTO]);
            $form_data[MutationInputProperties::APPLIESTO] = $appliesto ?? array();
        }

        return $form_data;
    }

    protected function addReferences()
    {
        return true;
    }

    protected function volunteer()
    {
        return false;
    }

    protected function supportsTitle()
    {
        // Not all post types support a title
        return true;
    }

    protected function moderate()
    {
        return \GD_CreateUpdate_Utils::moderate();
    }
}

