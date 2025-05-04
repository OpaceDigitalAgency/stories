# Admin Dashboard Component Relationships

This diagram shows the relationships between active components in the admin dashboard and identifies potential redundancies across environments.

## File Structure Component Map

```mermaid
graph TB
    subgraph "Core Files"
        dashboard[dashboard.php] 
        login[login.php]
        logout[logout.php]
        simple_auth[simple_auth.php]
    end
    
    subgraph "Includes"
        header[header.php]
        footer[footer.php]
        db_connect[db-connect.php]
        auth_check[auth-check.php]
        admin_funcs[admin-functions.php]
    end
    
    subgraph "Components"
        table_comp[table-component.php]
        form_comp[form-component.php]
        pagination[pagination-component.php]
        search[search-component.php]
        bulk_actions[bulk-actions-component.php]
        status_indicator[status-indicator-component.php]
    end
    
    subgraph "Content Pages"
        stories[stories.php]
        authors[authors.php]
        blog_posts[blog-posts.php]
        games[games.php]
        directory[directory-items.php]
        ai_tools[ai-tools.php]
        media[media.php]
        subscribers[subscribers.php]
        contacts[contacts.php]
        tags[tags.php]
    end
    
    subgraph "Form Pages"
        story_form[story-form.php]
        author_form[author-form.php]
        post_form[post-form.php]
        game_form[game-form.php]
        directory_form[directory-item-form.php]
        ai_tool_form[ai-tool-form.php]
        tag_form[tag-form.php]
    end
    
    subgraph "Action Handlers"
        save_story[save-story.php]
        save_author[save-author.php]
        save_post[save-post.php]
        save_game[save-game.php]
        save_directory[save-directory-item.php]
        save_ai_tool[save-ai-tool.php]
        save_tag[save-tag.php]
        delete_story[delete-story.php]
        delete_author[delete-author.php]
    end
    
    subgraph "Assets"
        css[CSS Files]
        js[JavaScript Files]
        img[Image Files]
    end
    
    subgraph "Redundant/Archival"
        style fill:#ffcccc
        archive[_archive folder]
        admin_new[admin-new folder]
        unused_crud[unused_crud_implementation]
        test_tools[test_tools.php]
        test_db[test-db-connection.php]
        fix_scripts[fix-*.php scripts]
    end
    
    %% Core File Relationships
    dashboard --> header
    dashboard --> footer
    dashboard --> db_connect
    login --> simple_auth
    
    %% Include Relationships
    header --> db_connect
    header --> auth_check
    auth_check --> simple_auth
    
    %% Content Page Relationships
    stories --> header
    stories --> footer
    stories --> table_comp
    stories --> pagination
    stories --> search
    stories --> bulk_actions
    
    %% Form Page Relationships
    story_form --> header
    story_form --> footer
    story_form --> form_comp
    
    %% JavaScript Relationships
    js --> stories
    js --> story_form
    
    %% Content Type Handler Relationships
    stories --> save_story
    stories --> delete_story
    story_form --> save_story
    
    %% Redundant Relationships
    archive -.-> unused_crud
    admin_new -.-> dashboard
    fix_scripts -.-> db_connect
    
    %% Legend
    classDef active fill:#d4f9d4,stroke:#333,stroke-width:1px
    classDef redundant fill:#ffcccc,stroke:#333,stroke-width:1px
    
    class dashboard,login,logout,simple_auth,header,footer,db_connect,auth_check,admin_funcs,table_comp,form_comp,pagination,search,bulk_actions,status_indicator,stories,authors,blog_posts,games,directory,ai_tools,media,subscribers,contacts,tags,story_form,author_form,post_form,game_form,directory_form,ai_tool_form,tag_form,save_story,save_author,save_post,save_game,save_directory,save_ai_tool,save_tag,delete_story,delete_author,css,js,img active
    
    class archive,admin_new,unused_crud,test_tools,test_db,fix_scripts redundant
```

## Multi-Environment Comparison

| Component | Local | cPanel | Git | Status |
|-----------|-------|--------|-----|--------|
| Admin Dashboard Core Files | ✅ | ✅ | ✅ | Active |
| Admin Content Pages | ✅ | ✅ | ✅ | Active |
| SimpleAuth System | ✅ | ✅ | ✅ | Active |
| Admin Components | ✅ | ✅ | ✅ | Active |
| Assets (CSS/JS) | ✅ | ✅ | ✅ | Active |
| _archive folder | ✅ | ✅ | ❓ | Redundant |
| admin-new folder | ❌ | ✅ | ❓ | Potentially newer version |
| test_*.php files | ✅ | ✅ | ❓ | Development only |
| fix_*.php scripts | ✅ | ✅ | ❓ | One-time fixes |
| wp_migration folder | ✅ | ✅ | ❓ | Migration tools |

## Deployment Recommendation

For a clean deployment to cPanel, the following structure is recommended:

```
/api.storiesfromtheweb.org/
├── admin/                    # Active admin dashboard
│   ├── assets/               # CSS, JS, and images
│   ├── content/              # Content management pages
│   ├── includes/             # Reusable components
│   ├── dashboard.php         # Main dashboard
│   ├── index.php             # Entry point
│   ├── login.php             # Login page
│   └── logout.php            # Logout functionality
├── api/                      # API endpoints
│   ├── v1/                   # API version 1
│   └── index.php             # API entry point
├── includes/                 # Shared includes
│   ├── config.php            # Centralized configuration
│   └── image_optimizer.php   # Image processing
├── public/                   # Publicly accessible files
│   └── optimize_image.php    # Public image tool
└── simple_auth.php           # Authentication system
```

All other folders and files from the current cPanel setup can be backed up and removed from the active server to reduce confusion and potential security issues.