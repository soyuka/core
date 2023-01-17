.PHONY: guide index references clean

all: guide references index 

guide:
	for d in ./guide/*.php; do \
			name=$$(basename $$d .php); \
			./bin/pdg pdg:guide "$$d" > "pages/guide/$$name.mdx"; \
	done

check:
	for d in ./guide/*.php; do \
			name=$$(basename $$d .php); \
			./bin/pdg pdg:check:guide "$$d"; \
	done

index:
	./bin/pdg pdg:index > "pages/sidebar.mdx"

references:
	./bin/pdg pdg:references

clean:
	rm -r pages/reference/*
	rm pages/guide/*.mdx
