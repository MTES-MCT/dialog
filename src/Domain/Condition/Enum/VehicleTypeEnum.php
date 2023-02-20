<?php

declare(strict_types=1);

namespace App\Domain\Condition\Enum;

enum VehicleTypeEnum: string
{
    public const ANIMAL_DRAWN_VEHICLES = 'animalDrawnVehicles';
    public const ELECTRIC_VEHICLES = 'electricVehicles';
    public const PASSENGER_CAR_WITH_TRAILER = 'passengerCarWithTrailer';
    public const MOTORIZED_VEHICLES = 'motorizedVehicles';
    public const NON_MOTORIZED_VEHICLES = 'nonMotorizedVehicles';
    public const GOODS_VEHICLES = 'goodsVehicles';
    public const HAND_CARTS = 'handcarts';
    public const SOLO_MOTORCYCLE = 'soloMotorcycle';
    public const MOTORIZED_VEHICLES_WITHOUT_NUMBER_PLATE = 'motorizedVehiclesWithoutNumberPlate';
    public const MOTOR_QUADRICYCLES = 'motorQuadricycles';
    public const MOTORIZED_PERSONAL_TRANSPORT_DEVICES = 'motorisedPersonalTransportDevices';
}
