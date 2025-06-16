<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Concerns\HasRoutes;
use Filament\Pages\Page;
use Filament\Pages\SimplePage;

class VerificationPending extends SimplePage
{
    use HasRoutes;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.verification-pending';



    public static function getUrl(array $parameters = [],bool $isAbsolute=true,?string $panel=null): string
    {

        if (blank($panel)||Filament::getPanel($panel)->hasTenancy()){
            $parameters['tenant']??=($tenant = Filament::getTenant());
        }

        return route(static::getRouteName($panel),$parameters,$isAbsolute);
    }


    private static function getRouteName(?string $panel=null):string
    {
        $panel=$panel? Filament::getPanel($panel):Filament::getCurrentPanel();
        $routeName=static::getRelativeRouteName();
        return $panel->generateRouteName($routeName);
    }
    public static function getCluster(): ?string
    {
        return null; // Indicates this page doesn't belong to a cluster
    }

    public static function registerNavigationItems():void
    {
        return ;
    }

}
