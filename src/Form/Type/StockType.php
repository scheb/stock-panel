<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currencies = ["EUR" => "EUR", "USD" => "USD"];
        $builder
            ->add("symbol", null, ['label' => 'Symbol'])
            ->add("name", null, ['label' => 'Name'])
            ->add("currency", ChoiceType::class, ['choices' => $currencies, 'label' => 'Währung'])
            ->add("initialPrice", NumberType::class, ['label' => 'Erster Kurs'])
            ->add("quantity", NumberType::class, ['label' => 'Anzahl'])
            ->add("displayChart", null, ['required' => false, 'label' => 'Chart anzeigen?']);
    }

    public function getName()
    {
        return "stock";
    }
}
