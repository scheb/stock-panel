<?php

namespace App\Form\Type;

use App\Entity\Stock;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currencies = ["EUR" => "EUR", "USD" => "USD"];
        $comparators = ['Kurs fällt unter' => Stock::COMPARATOR_BELOW, 'Kurs steigt über' => Stock::COMPARATOR_ABOVE];
        $builder
            ->add("name", null, ['label' => 'Name'])
            ->add('symbols', TextType::class, ['label' => 'Symbols', 'required' => true])
            ->add("category", null, ['label' => 'Kategorie (optional)', 'required' => false])
            ->add("quantity", NumberType::class, ['label' => 'Anzahl', 'required' => false, 'scale' => 6])
            ->add("initialPrice", NumberType::class, ['label' => 'Erster Kurs', 'required' => false])
            ->add("currency", ChoiceType::class, ['choices' => $currencies, 'label' => 'Währung'])
            ->add("displayChart", null, ['required' => false, 'label' => 'Chart anzeigen?'])
            ->add("favourite", null, ['required' => false, 'label' => 'Favoriten?'])
            ->add("alertThreshold", NumberType::class, ['required' => false, 'label' => 'Preisgrenze'])
            ->add("alertDynamicThresholdPercent", NumberType::class, ['required' => false, 'label' => 'Dyn. Preisgrenze (Prozent)'])
            ->add("alertComparator", ChoiceType::class, ['choices' => $comparators, 'required' => false, 'label' => 'Vergleich'])
            ->add("alertMessage", null, ['required' => false, 'label' => 'Nachricht'])
        ;

        $builder->get('symbols')
            ->addModelTransformer(
                new CallbackTransformer(
                    function ($tagsAsArray): string {
                        if (null === $tagsAsArray) {
                            return '';
                        }
                        // transform the array to a string
                        return implode(', ', $tagsAsArray);
                    },
                    function ($tagsAsString): array {
                        // transform the string back to an array
                        $values = explode(', ', $tagsAsString);
                        $values = array_map('trim', $values);
                        $values = array_filter($values, fn ($value): bool => strlen($value) > 0);
                        return array_values($values);
                    },
                )
            );
    }

    public function getName(): string
    {
        return "stock";
    }
}
