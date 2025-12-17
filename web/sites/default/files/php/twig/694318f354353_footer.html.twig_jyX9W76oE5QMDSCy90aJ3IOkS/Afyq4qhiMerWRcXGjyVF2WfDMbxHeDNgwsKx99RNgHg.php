<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* @thex/template-parts/footer/footer.html.twig */
class __TwigTemplate_e705bb500d707ae3892581bd491053ac extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_top", [], "any", false, false, true, 1)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 2
            yield "  ";
            yield from $this->load("@thex/template-parts/footer/footer-top.html.twig", 2)->unwrap()->yield($context);
        }
        // line 4
        if ((((CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_one", [], "any", false, false, true, 4) || CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_two", [], "any", false, false, true, 4)) || CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_three", [], "any", false, false, true, 4)) || CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_four", [], "any", false, false, true, 4))) {
            // line 5
            yield "  ";
            yield from $this->load("@thex/template-parts/footer/footer-blocks.html.twig", 5)->unwrap()->yield($context);
        }
        // line 7
        if ((CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_bottom_left", [], "any", false, false, true, 7) || CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_bottom_right", [], "any", false, false, true, 7))) {
            // line 8
            yield "  ";
            yield from $this->load("@thex/template-parts/footer/footer-bottom-blocks.html.twig", 8)->unwrap()->yield($context);
        }
        // line 10
        yield from $this->load("@thex/template-parts/footer/footer-bottom.html.twig", 10)->unwrap()->yield($context);
        // line 11
        if ((($tmp = ($context["scrolltotop_on"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 12
            yield "  <div class=\"scrolltop\"><i class=\"icon-arrow-up\"></i></div>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["page", "scrolltotop_on"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@thex/template-parts/footer/footer.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  66 => 12,  64 => 11,  62 => 10,  58 => 8,  56 => 7,  52 => 5,  50 => 4,  46 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@thex/template-parts/footer/footer.html.twig", "/var/www/html/web/themes/contrib/thex/templates/template-parts/footer/footer.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 1, "include" => 2];
        static $filters = [];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'include'],
                [],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
