<?php

declare(strict_types=1);

namespace App\Domain\Condition\Enum;

enum VehicleTypeEnum: string
{
    case ANIMAL_DRAWN_VEHICLES = 'animalDrawnVehicles';
    case ELECTRIC_VEHICLES = 'electricVehicles';
    case PASSENGER_CAR_WITH_TRAILER = 'passengerCarWithTrailer';
    case MOTORIZED_VEHICLES = 'motorizedVehicles';
    case NON_MOTORIZED_VEHICLES = 'nonMotorizedVehicles';
    case GOODS_VEHICLES = 'goodsVehicles';
    case HAND_CARTS = 'handcarts';
    case SOLO_MOTORCYCLE = 'soloMotorcycle';
    case MOTORIZED_VEHICLES_WITHOUT_NUMBER_PLATE = 'motorizedVehiclesWithoutNumberPlate';
    case MOTOR_QUADRICYCLES = 'motorQuadricycles';
    case MOTORIZED_PERSONAL_TRANSPORT_DEVICES = 'motorisedPersonalTransportDevices';
}
