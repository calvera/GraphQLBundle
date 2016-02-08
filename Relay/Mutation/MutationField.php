<?php

namespace Overblog\GraphBundle\Relay\Mutation;

use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Utils;
use Overblog\GraphBundle\Definition\FieldInterface;
use Overblog\GraphBundle\Definition\MergeFieldTrait;

class MutationField implements FieldInterface
{
    use MergeFieldTrait;

    public function toFieldsDefinition(array $config)
    {
        Utils::invariant(!empty($config['name']), 'Every type is expected to have name');

        Config::validate($config, [
            'name' => Config::STRING | Config::REQUIRED,
            'mutateAndGetPayload' => Config::CALLBACK | Config::REQUIRED,
            'payloadType' => Config::OBJECT_TYPE | Config::CALLBACK | Config::REQUIRED,
            'inputType' => Config::INPUT_TYPE | Config::CALLBACK | Config::REQUIRED,
            'description' => Config::STRING
        ]);

        $name = $config['name'];

        $mutateAndGetPayload = $config['mutateAndGetPayload'];
        $description = isset($config['description']) ? $config['description'] : null;
        $payloadType = $config['payloadType'];
        $inputType = $config['inputType'];

        return [
            'name' => $name,
            'description' => $description,
            'type' => $payloadType,
            'args' => [
                'input' => ['type' =>  Type::nonNull($inputType)]
            ],
            'resolve' => function($_, $input, $info) use ($mutateAndGetPayload, $name) {
                if (empty($input['input'])) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            "Field \"%s\" argument \"input\" of type \"%sInput!\" is required but not provided.",
                            $name,
                            $name
                        )
                    );
                }

                $payload = $mutateAndGetPayload($input['input'], $info);
                $payload['clientMutationId'] = $input['input']['clientMutationId'];

                return $payload;
            }
        ];
    }
}
