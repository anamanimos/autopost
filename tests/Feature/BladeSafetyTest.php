<?php

namespace Tests\Feature;

use App\View\SafeBladeCompiler;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BladeSafetyTest extends TestCase
{
    public function test_safe_blade_compiler_is_bound(): void
    {
        $compiler = app('blade.compiler');
        $this->assertInstanceOf(SafeBladeCompiler::class, $compiler);
    }

    public function test_safe_blade_compiler_compiles_without_error(): void
    {
        $rendered = Blade::render('<div>Hello {{ $name }}</div>', ['name' => 'World']);
        $this->assertEquals('<div>Hello World</div>', $rendered);
    }
}
