.PHONY: build clean help

# Színek a terminál kimenethez
GREEN := \033[0;32m
YELLOW := \033[0;33m
BLUE := \033[0;34m
NC := \033[0m # No Color

help:
	@echo "$(BLUE)WordPress Plugin Builder$(NC)"
	@echo ""
	@echo "Elérhető parancsok:"
	@echo "  $(GREEN)make build$(NC)  - Plugin kiválasztása és ZIP készítése"
	@echo "  $(GREEN)make clean$(NC)  - Összes generált ZIP fájl törlése"
	@echo "  $(GREEN)make help$(NC)   - Súgó megjelenítése"

build:
	@echo "$(BLUE)WordPress Plugin Builder$(NC)"
	@echo ""
	@echo "Elérhető pluginok:"
	@echo ""
	@# Plugin könyvtárak dinamikus felderítése
	@plugins=$$(find . -maxdepth 1 -type d -not -name '.' -not -name '.git' -not -name '.*' | sed 's|^\./||' | sort); \
	if [ -z "$$plugins" ]; then \
		echo "$(YELLOW)Nem találhatók plugin könyvtárak!$(NC)"; \
		exit 1; \
	fi; \
	i=1; \
	declare -A plugin_map; \
	for plugin in $$plugins; do \
		if [ -f "$$plugin/$$plugin.php" ]; then \
			echo "  $(GREEN)$$i)$(NC) $$plugin"; \
			plugin_map[$$i]=$$plugin; \
			i=$$((i+1)); \
		fi; \
	done; \
	echo ""; \
	if [ $$i -eq 1 ]; then \
		echo "$(YELLOW)Nem találhatók érvényes pluginok!$(NC)"; \
		exit 1; \
	fi; \
	read -p "Válassz egy plugint (szám): " choice; \
	echo ""; \
	selected_plugin=""; \
	j=1; \
	for plugin in $$plugins; do \
		if [ -f "$$plugin/$$plugin.php" ]; then \
			if [ $$j -eq $$choice ]; then \
				selected_plugin=$$plugin; \
				break; \
			fi; \
			j=$$((j+1)); \
		fi; \
	done; \
	if [ -z "$$selected_plugin" ]; then \
		echo "$(YELLOW)Érvénytelen választás!$(NC)"; \
		exit 1; \
	fi; \
	echo "$(BLUE)Kiválasztva:$(NC) $$selected_plugin"; \
	echo ""; \
	plugin_file="$$selected_plugin/$$selected_plugin.php"; \
	if [ ! -f "$$plugin_file" ]; then \
		echo "$(YELLOW)Hiba: $$plugin_file nem található!$(NC)"; \
		exit 1; \
	fi; \
	version=$$(grep -i "^Version:" "$$plugin_file" | head -n1 | awk '{print $$2}' | tr -d '\r'); \
	if [ -z "$$version" ]; then \
		echo "$(YELLOW)Hiba: Nem található verzió információ a $$plugin_file fájlban!$(NC)"; \
		exit 1; \
	fi; \
	echo "$(BLUE)Verzió:$(NC) $$version"; \
	echo ""; \
	zip_name="$$selected_plugin-$$version.zip"; \
	echo "$(BLUE)ZIP fájl készítése:$(NC) $$zip_name"; \
	if [ -f "$$zip_name" ]; then \
		rm "$$zip_name"; \
		echo "$(YELLOW)Korábbi ZIP fájl törölve.$(NC)"; \
	fi; \
	cd "$$selected_plugin" && zip -r "../$$zip_name" . -x "*.git*" -x "*.DS_Store" -x "*__MACOSX*" > /dev/null; \
	if [ $$? -eq 0 ]; then \
		echo "$(GREEN)✓ Sikeres!$(NC) A ZIP fájl elkészült: $$zip_name"; \
	else \
		echo "$(YELLOW)Hiba történt a ZIP fájl készítése során!$(NC)"; \
		exit 1; \
	fi

clean:
	@echo "$(BLUE)ZIP fájlok törlése...$(NC)"
	@removed=0; \
	for zip in *.zip; do \
		if [ -f "$$zip" ]; then \
			rm "$$zip"; \
			echo "$(YELLOW)Törölve:$(NC) $$zip"; \
			removed=$$((removed+1)); \
		fi; \
	done; \
	if [ $$removed -eq 0 ]; then \
		echo "$(GREEN)Nincs törlendő ZIP fájl.$(NC)"; \
	else \
		echo "$(GREEN)✓ $$removed ZIP fájl törölve.$(NC)"; \
	fi
