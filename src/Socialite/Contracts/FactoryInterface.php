<?php

namespace Pgyf\Opensdk\Kernel\Socialite\Contracts;

const ABNF_APP_ID = 'app_id';
const ABNF_APP_SECRET = 'app_secret';
const ABNF_OPEN_ID = 'open_id';
const ABNF_TOKEN = 'token';

interface FactoryInterface
{
    public function config(\Pgyf\Opensdk\Kernel\Socialite\Config $config): self;

    public function create(string $name): ProviderInterface;

    public function getResolvedProviders(): array;

    public function buildProvider(string $provider, array $config): ProviderInterface;
}