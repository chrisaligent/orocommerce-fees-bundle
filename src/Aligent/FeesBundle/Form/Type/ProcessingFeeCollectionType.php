<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProcessingFeeCollectionType extends AbstractType
{
    const NAME = 'aligent_processing_fee_collection';

    protected PaymentMethodProviderInterface $methodProvider;
    protected PaymentMethodViewProviderInterface $methodViewProvider;

    public function __construct(
        PaymentMethodProviderInterface $methodProvider,
        PaymentMethodViewProviderInterface $methodViewProvider,
    ) {
        $this->methodProvider = $methodProvider;
        $this->methodViewProvider = $methodViewProvider;
    }

    /**
     * @param FormBuilderInterface<mixed> $builder
     * @param array<string,mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {

                $currentSettings = [];
                $data = $event->getData();
                if (null === $data) {
                    return;
                }

                /**
                 * Build array of current configuration settings
                 */
                if (is_array($data) && count($data) > 0) {
                    foreach ($data as $settingsRow) {
                        $currentSettings[$settingsRow['method']] = $settingsRow;
                    }
                }

                $settings = [];
                foreach ($this->getDefaultMethodSettings() as $methodId => $methodSettings) {
                    if (array_key_exists($methodId, $currentSettings)) {
                        /**
                         * This Method has already been configured, overwrite default values
                         */
                        $methodSettings['percentage'] = $currentSettings[$methodId]['percentage'];
                    }

                    $settings[$methodId] = $methodSettings;
                }

                $event->setData($settings);
            },
            50
        );
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    protected function getDefaultMethodSettings(): array
    {
        $result = [];
        foreach ($this->methodProvider->getPaymentMethods() as $method) {
            $methodId = $method->getIdentifier();
            $label = $this
                ->methodViewProvider->getPaymentMethodView($methodId)
                ->getAdminLabel();

            $result[$methodId] = [
                'method' => $methodId,
                'label' => $label,
                'percentage' => 0.0,
            ];
        }

        return $result;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => ProcessingFeeType::class,
            'show_form_when_empty' => true,
            'allow_add' => false,
            'allow_delete' => false,
            'mapped' => true,
            'label' => false,
            'error_bubbling' => false,
            'handle_primary' => false,
        ]);
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
