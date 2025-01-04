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
            ->add("category", null, ['label' => 'Kategorie (optional)', 'required' => false])
            ->add("currency", ChoiceType::class, ['choices' => $currencies, 'label' => 'Währung'])
            ->add("initialPrice", NumberType::class, ['label' => 'Erster Kurs', 'required' => false])
            ->add("quantity", NumberType::class, ['label' => 'Anzahl', 'required' => false])
            ->add("displayChart", null, ['required' => false, 'label' => 'Chart anzeigen?'])
            ->add("favourite", null, ['required' => false, 'label' => 'Favoriten?'])
        ;
    }

    public function getName()
    {
        return "stock";
    }
}
