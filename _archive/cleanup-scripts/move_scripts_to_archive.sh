#!/bin/bash

# Create the archive directory if it doesn't exist
mkdir -p stories-backend/_archive/scripts

# List of obsolete fix scripts to archive
scripts=(
  "fix_admin_form.php"
  "fix_auth_for_save.php"
  "fix_case_sensitivity.php"
  "fix_controller_inheritance.php"
  "fix_dashboard.php"
  "fix_database_schema.php"
  "fix_directory_items_and_ai_tools.php"
  "fix_games_endpoint.php"
  "fix_navigation_and_dropdowns.php"
  "add_debug_logging.php"
  "add_slug_to_games.php"
  "all_in_one_fix.php"
  "check_files.php"
  "critical_fix.php"
  "debug_admin_interface.php"
  "deploy_auth_fix.sh"
  "find_admin.php"
  "move_files.php"
  "fix_admin_boolean_fields.php"
  "fix_admin_interface_emergency.php"
  "fix_ai_tools_controller.php"
  "fix_auth_middleware.php"
  "fix_case_once_and_for_all.php"
  "fix_config_simple.php"
  "fix_controller_class.php"
  "fix_controller_loading.php"
  "fix_controllers_use_statement.php"
  "fix_database_credentials.php"
  "fix_debug_mode.php"
  "fix_directories.php"
  "fix_directory_items_controller.php"
  "fix_dropdowns.php"
  "fix_duplicate_stories.php"
  "fix_form_submission.php"
  "fix_games_endpoint_config.php"
  "fix_login.php"
  "fix_navigation_only.php"
  "fix_redirects.php"
  "fix_response_class.php"
  "fix_router_and_config.php"
  "fix_story_flags.php"
)

# Move each script to the archive directory
for script in "${scripts[@]}"; do
  if [ -f "stories-backend/$script" ]; then
    echo "Moving $script to archive..."
    mv "stories-backend/$script" "stories-backend/_archive/scripts/"
  else
    echo "Script $script not found, skipping..."
  fi
done

echo "Script archiving complete!"