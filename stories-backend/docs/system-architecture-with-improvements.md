# Stories from the Web - System Architecture with Improvement Areas

This document provides a visual representation of the current system architecture and highlights the areas that need improvement as part of the cleanup and standardization plan.

## Current Architecture Overview

```mermaid
graph TD
    A[Users] -->|View Content| F[Astro Frontend]
    F -->|API Requests| B[PHP API]
    B -->|Reads/Writes| C[MySQL DB]
    G[Content Creators] -->|Manage Content| H[Admin Panel]
    H -->|CRUD| B
    B -->|Direct Queries| C
    
    classDef frontend fill:#ccf,stroke:#333,stroke-width:1px
    classDef backend fill:#ffc,stroke:#333,stroke-width:1px
    classDef database fill:#cfc,stroke:#333,stroke-width:1px
    classDef admin fill:#9ff,stroke:#333,stroke-width:1px
    classDef user fill:#f9f,stroke:#333,stroke-width:1px
    classDef improvement fill:#fcc,stroke:#f00,stroke-width:2px,stroke-dasharray: 5 5
    
    class A,G user
    class F frontend
    class B backend
    class C database
    class H admin
```

## Areas Needing Improvement

```mermaid
graph TD
    A[Users] -->|View Content| F[Astro Frontend]
    F -->|API Requests| B[PHP API]
    B -->|Reads/Writes| C[MySQL DB]
    G[Content Creators] -->|Manage Content| H[Admin Panel]
    H -->|CRUD| B
    B -->|Direct Queries| C
    
    %% Improvement Areas
    I1[API Inconsistencies] -.->|Needs Standardization| B
    I2[Redundant PHP Scripts] -.->|Needs Cleanup| B
    I3[Admin Interface Issues] -.->|Needs Reliability Improvements| H
    I4[Documentation Fragmentation] -.->|Needs Consolidation| D[Documentation]
    
    classDef frontend fill:#ccf,stroke:#333,stroke-width:1px
    classDef backend fill:#ffc,stroke:#333,stroke-width:1px
    classDef database fill:#cfc,stroke:#333,stroke-width:1px
    classDef admin fill:#9ff,stroke:#333,stroke-width:1px
    classDef user fill:#f9f,stroke:#333,stroke-width:1px
    classDef improvement fill:#fcc,stroke:#f00,stroke-width:2px
    classDef docs fill:#ffe,stroke:#333,stroke-width:1px
    
    class A,G user
    class F frontend
    class B backend
    class C database
    class H admin
    class I1,I2,I3,I4 improvement
    class D docs
```

## PHP Scripts Organization

```mermaid
graph TD
    subgraph Current Scripts
        S1[Essential Scripts]
        S2[Redundant Scripts]
        S3[Obsolete Fix Scripts]
        S4[Diagnostic Scripts]
    end
    
    subgraph After Cleanup
        NS1[Essential Scripts]
        NS2[Consolidated Diagnostic Scripts]
        NS3[Archive]
    end
    
    S1 --> NS1
    S2 --> NS3
    S3 --> NS3
    S4 --> NS2
    
    classDef current fill:#ffc,stroke:#333,stroke-width:1px
    classDef after fill:#cfc,stroke:#333,stroke-width:1px
    classDef archive fill:#fcf,stroke:#333,stroke-width:1px
    
    class S1,S2,S3,S4 current
    class NS1,NS2 after
    class NS3 archive
```

## Documentation Organization

```mermaid
graph TD
    subgraph Current Documentation
        D1[Core Documentation]
        D2[Deployment Guides]
        D3[Fix Documentation]
        D4[Test Documentation]
    end
    
    subgraph After Consolidation
        ND1[Core Documentation]
        ND2[Consolidated Deployment Guide]
        ND3[Archive]
    end
    
    D1 --> ND1
    D2 --> ND2
    D3 --> ND3
    D4 --> ND3
    
    classDef current fill:#ffc,stroke:#333,stroke-width:1px
    classDef after fill:#cfc,stroke:#333,stroke-width:1px
    classDef archive fill:#fcf,stroke:#333,stroke-width:1px
    
    class D1,D2,D3,D4 current
    class ND1,ND2 after
    class ND3 archive
```

## Implementation Phases

```mermaid
graph LR
    P1[Phase 1: Preparation] --> P2[Phase 2: PHP Scripts Cleanup]
    P2 --> P3[Phase 3: Documentation Consolidation]
    P3 --> P4[Phase 4: Testing and Verification]
    
    classDef phase fill:#ccf,stroke:#333,stroke-width:1px
    
    class P1,P2,P3,P4 phase
```

## Recovery Strategy

```mermaid
flowchart TD
    A[Accidental Deletion] --> B{Recovery Method}
    B -->|Git-Based| C[Check Archive Branches]
    B -->|Physical Archive| D[Check Archive Directories]
    
    C --> E[Restore from Tags]
    D --> F[Move Files Back]
    
    E --> G[File Recovered]
    F --> G
    
    classDef problem fill:#fcc,stroke:#f00,stroke-width:2px
    classDef decision fill:#ffc,stroke:#333,stroke-width:1px
    classDef action fill:#ccf,stroke:#333,stroke-width:1px
    classDef result fill:#cfc,stroke:#333,stroke-width:1px
    
    class A problem
    class B decision
    class C,D,E,F action
    class G result
```

## Conclusion

This visual representation of the Stories from the Web architecture highlights the key areas that need improvement as part of the cleanup and standardization plan. By addressing these areas, we'll create a more maintainable, reliable, and well-documented codebase that will serve as a solid foundation for future development.

The implementation plan outlined in the [Revised Cleanup Plan](revised-cleanup-plan.md) provides a step-by-step approach to addressing these improvement areas while ensuring that we can quickly recover if something gets moved or deleted by accident.