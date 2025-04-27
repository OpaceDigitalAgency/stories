# Stories from the Web - Documentation Index

This document serves as a central index for all documentation related to the Stories from the Web platform. It provides links to all documentation files and recommendations for which existing documentation files to keep, update, or remove.

## Table of Contents

- [New Documentation](#new-documentation)
- [Existing Documentation](#existing-documentation)
- [Documentation Recommendations](#documentation-recommendations)

## New Documentation

The following documentation files have been created as part of the comprehensive cleanup and standardization plan:

### Core Documentation

1. [**Stories Cleanup Plan**](../stories-cleanup-plan.md) - Comprehensive plan for cleaning up and standardizing the codebase
2. [**System Architecture**](system-architecture.md) - Detailed documentation of the system architecture with diagrams
3. [**Comprehensive System Architecture**](comprehensive-system-architecture.md) - Complete and detailed system architecture documentation with explanations of all components
4. [**Database Schema**](../database-schema.md) - Complete documentation of the database schema
5. [**API Documentation**](../api-documentation.md) - Comprehensive documentation of all API endpoints
6. [**PHP Scripts Cleanup Guide**](../php-scripts-cleanup-guide.md) - Guide for cleaning up PHP scripts
7. [**Implementation Plan**](../implementation-plan.md) - Detailed implementation plan
8. [**Project Cleanup Summary**](../project-cleanup-summary.md) - Summary and index of all documentation

### Consolidated Documentation

9. [**Consolidated Deployment Guide**](consolidated-deployment-guide.md) - Comprehensive guide for deploying the platform, consolidating information from various deployment-related files

### Visual Documentation

10. [**System Architecture (HTML)**](system-architecture.html) - Interactive HTML version of the system architecture with Mermaid.js diagrams

### Troubleshooting Documentation

11. [**Known Issues and Fixes**](KNOWN_ISSUES_AND_FIXES.md) - Documentation of known issues and their solutions

## Existing Documentation

The following documentation files already existed in the project:

1. [**README.md**](../README.md) - Project overview and basic information
2. [**PLANNING.md**](../PLANNING.md) - Project architecture, goals, and constraints
3. [**PROGRESS.md**](../PROGRESS.md) - Log of completed work
4. [**DEPLOYMENT.md**](../DEPLOYMENT.md) - Deployment guide for the project
5. [**FTP_DEPLOYMENT.md**](../FTP_DEPLOYMENT.md) - Guide for FTP deployment
6. [**GIT_DEPLOYMENT.md**](../GIT_DEPLOYMENT.md) - Guide for Git deployment
7. [**GITHUB_DEPLOY.md**](../GITHUB_DEPLOY.md) - Guide for GitHub Actions deployment
8. [**API_CONNECTIVITY_FIX.md**](../API_CONNECTIVITY_FIX.md) - Guide for fixing API connectivity issues
9. [**system-documentation.html**](../system-documentation.html) - HTML version of system documentation

## Documentation Recommendations

Based on the analysis of existing documentation, here are recommendations for which files to keep, update, or remove:

### Files to Keep and Update

1. **README.md** - Keep and update with current project status and links to new documentation
2. **PLANNING.md** - Keep as a historical record of project planning, but update with current architecture information
3. **PROGRESS.md** - Keep as a historical record of progress, but update with recent changes

### Files to Replace with Consolidated Documentation

4. **DEPLOYMENT.md** - Replace with [Consolidated Deployment Guide](consolidated-deployment-guide.md)
5. **FTP_DEPLOYMENT.md** - Replace with [Consolidated Deployment Guide](consolidated-deployment-guide.md)
6. **GIT_DEPLOYMENT.md** - Replace with [Consolidated Deployment Guide](consolidated-deployment-guide.md)
7. **GITHUB_DEPLOY.md** - Replace with [Consolidated Deployment Guide](consolidated-deployment-guide.md)
8. **system-documentation.html** - Replace with [Comprehensive System Architecture](comprehensive-system-architecture.md) and [System Architecture (HTML)](system-architecture.html)

### Files to Keep for Reference

9. **API_CONNECTIVITY_FIX.md** - Keep as a reference for API connectivity issues, but note that it's been incorporated into the new documentation

### Files to Remove

10. **test_push.md** - Remove (test file)
11. **test2.md** - Remove (test file)
12. **test3.md** - Remove (test file)
13. **fix-plan.md** - Remove (superseded by new documentation)
14. **fix-styling-plan.md** - Remove (superseded by new documentation)

## Documentation Structure

The recommended documentation structure is as follows:

```
/
├── README.md                       # Project overview and links to documentation
├── PLANNING.md                     # Historical project planning
├── PROGRESS.md                     # Historical progress log
├── stories-cleanup-plan.md         # Comprehensive cleanup plan
├── system-architecture.md          # System architecture documentation
├── database-schema.md              # Database schema documentation
├── api-documentation.md            # API documentation
├── php-scripts-cleanup-guide.md    # PHP scripts cleanup guide
├── implementation-plan.md          # Implementation plan
├── project-cleanup-summary.md      # Project cleanup summary
├── docs/                           # Documentation directory
│   ├── documentation-index.md      # This file
│   ├── system-architecture.md      # System architecture with diagrams
│   ├── system-architecture.html    # Interactive HTML version with diagrams
│   ├── comprehensive-system-architecture.md # Complete system architecture documentation
│   ├── consolidated-deployment-guide.md # Consolidated deployment guide
│   └── KNOWN_ISSUES_AND_FIXES.md   # Known issues and their solutions
└── API_CONNECTIVITY_FIX.md         # Reference for API connectivity issues
```

## Next Steps

1. Update README.md with links to new documentation
2. Update PLANNING.md with current architecture information
3. Update PROGRESS.md with recent changes
4. Remove obsolete documentation files
5. Implement the cleanup and standardization plan as outlined in the implementation plan