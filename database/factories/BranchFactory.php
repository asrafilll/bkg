<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Branch #' . $this->faker->randomNumber(3, true),
            'order_number_prefix' => $this->faker->colorName(),
        ];
    }
}