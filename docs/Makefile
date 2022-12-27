.PHONY: guide

guide:
	for d in ./guide/*.php; do \
			name=$$(basename $$d .php); \
			./bin/pdg pdg:guide "$$d" > "pages/guide/$$name.mdx"; \
	done

index:
	./bin/pdg pdg:index > "pages/sidebar.mdx"

reference:
	mkdir -p pages/reference/Symfony/Bundle/DependencyInjection
	php src/generate-reference.php ../src/Symfony/Bundle/DependencyInjection/Configuration.php > pages/reference/Symfony/Bundle/DependencyInjection/Configuration.mdx

