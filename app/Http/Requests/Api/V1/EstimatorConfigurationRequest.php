<?php

namespace App\Http\Requests\Api\V1;

use App\Services\EstimatorConfigurator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Une configuration complète de l'estimateur : réglages et éléments à créer ou modifier.
 * Les mêmes règles que EstimatorConfigurator, qui vérifie en plus les références entre éléments.
 */
class EstimatorConfigurationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return EstimatorConfigurator::rules();
    }
}
