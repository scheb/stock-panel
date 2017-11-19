<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currencies = ["EUR" => "EUR", "USD" => "USD"];
        $builder
            ->add("symbol")
            ->add("name")
            ->add("currency", ChoiceType::class, ['choices' => $currencies])
            ->add("initialPrice")
            ->add("quantity")
            ->add("displayChart", null, ['required' => false]);
    }

    public function getName()
    {
        return "stock";
    }
}
