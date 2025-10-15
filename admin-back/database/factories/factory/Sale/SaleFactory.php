<?php

namespace Database\Factories\factory\Sale;
use App\Models\User;
use App\Models\Sale;
use App\Models\Client;
use App\Models\Sucursale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // $date_sales = $this->faker->dateTimeBetween("2024-01-01 00:00:00", "2024-12-25 23:59:59");
        $date_sales = $this->faker->dateTimeBetween("2025-01-01 00:00:00", "2025-12-25 23:59:59");

        $client = Client::inRandomOrder()->first();
        return [
            "user_id" => User::where("role_id",8)->inRandomOrder()->first()->id,
            "client_id" => $client->id,
            "type_client" => $client->type_client,
            "sucursal_id" => Sucursale::where("status",'Activo')->inRandomOrder()->first()->id,
            "subtotal" => 0,
            "discount" => 0,
            "total" => 0,
            "iva" => 0,
            "state" => $this->faker->randomElement([1,2]),
            "state_mayment" => 1,
            "debt" => 0,
            "paid_out" => 0,
            "description" => $this->faker->text($maxNbChars = 300),
            "created_at" => $date_sales,
            "updated_at" => $date_sales,
        ];
    }
}
