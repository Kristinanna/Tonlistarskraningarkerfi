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

/* themes/contrib/thex/templates/layout/page.html.twig */
class __TwigTemplate_8e98e633387d7b207813a299b20e4c36 extends Template
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
        if ((CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header_top_left", [], "any", false, false, true, 1) || CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header_top_right", [], "any", false, false, true, 1))) {
            // line 2
            yield "  ";
            yield from $this->load("@thex/template-parts/header/header-top.html.twig", 2)->unwrap()->yield($context);
        }
        // line 4
        yield from $this->load("@thex/template-parts/header/header.html.twig", 4)->unwrap()->yield($context);
        // line 5
        if (( !($context["is_front"] ?? null) && CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "page_header", [], "any", false, false, true, 5))) {
            // line 6
            yield "  ";
            yield from $this->load("@thex/template-parts/header/header-page.html.twig", 6)->unwrap()->yield($context);
        }
        // line 8
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "highlighted", [], "any", false, false, true, 8)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 9
            yield "  ";
            yield from $this->load("@thex/template-parts/highlighted.html.twig", 9)->unwrap()->yield($context);
        }
        // line 11
        yield "<div class=\"main-wrapper\">
  <div class=\"container\">
    <a id=\"main-content\" tabindex=\"-1\"></a>
    <div class=\"main-container\">
      <main id=\"main\" class=\"main-content\">
        ";
        // line 16
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "content_top", [], "any", false, false, true, 16)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 17
            yield "          ";
            yield from $this->load("@thex/template-parts/content-parts/content_top.html.twig", 17)->unwrap()->yield($context);
            // line 18
            yield "        ";
        }
        // line 19
        yield "        <div class=\"node-content\">
          ";
        // line 20
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "content", [], "any", false, false, true, 20), "html", null, true);
        yield "
        </div>
        ";
        // line 22
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "content_bottom", [], "any", false, false, true, 22)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 23
            yield "          ";
            yield from $this->load("@thex/template-parts/content-parts/content_bottom.html.twig", 23)->unwrap()->yield($context);
            // line 24
            yield "        ";
        }
        // line 25
        yield "      </main>
    ";
        // line 26
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_first", [], "any", false, false, true, 26)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 27
            yield "      ";
            yield from $this->load("@thex/template-parts/sidebar/sidebar_left.html.twig", 27)->unwrap()->yield($context);
            // line 28
            yield "    ";
        }
        // line 29
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_second", [], "any", false, false, true, 29)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 30
            yield "      ";
            yield from $this->load("@thex/template-parts/sidebar/sidebar_right.html.twig", 30)->unwrap()->yield($context);
            // line 31
            yield "    ";
        }
        // line 32
        yield "    </div><!--/main-container -->
  </div><!--/container -->
</div><!--/main-wrapper -->
";
        // line 35
        yield from $this->load("@thex/template-parts/footer/footer.html.twig", 35)->unwrap()->yield($context);
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["page", "is_front"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/thex/templates/layout/page.html.twig";
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
        return array (  120 => 35,  115 => 32,  112 => 31,  109 => 30,  106 => 29,  103 => 28,  100 => 27,  98 => 26,  95 => 25,  92 => 24,  89 => 23,  87 => 22,  82 => 20,  79 => 19,  76 => 18,  73 => 17,  71 => 16,  64 => 11,  60 => 9,  58 => 8,  54 => 6,  52 => 5,  50 => 4,  46 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/thex/templates/layout/page.html.twig", "/var/www/html/web/themes/contrib/thex/templates/layout/page.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 1, "include" => 2];
        static $filters = ["escape" => 20];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'include'],
                ['escape'],
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
