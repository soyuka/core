.PHONY: guide

guide:
	for d in ./guide/*.php; do \
			name=$$(basename $$d .php); \
			./bin/pdg pdg:guide "$$d" > "pages/guide/$$name.mdx"; \
	done

index:
	./bin/pdg pdg:index > "pages/sidebar.mdx"

references:
	./bin/pdg pdg:generate-references
