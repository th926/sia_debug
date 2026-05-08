#ifndef __cplusplus
#error This can only be compiled with C++
#endif
#pragma once

#include <mysqlx/xdevapi.h>

class Searcher {
protected:
    mysqlx::Table &table;
    int attachment_id;
public:
    Searcher(mysqlx::Table &table, int attachment_id);
    inline std::string caiq(std::string, std::string);
    inline std::vector<int> flatten(mysqlx::RowResult &rowres);
};

class OptionSearch : Searcher {
public:
    std::vector<int> widgets();
    std::vector<int> customizer_usage();
    std::vector<int> options();
};

class PostmetaSearch : Searcher {
public:
    std::vector<int> featured_image();
    std::vector<int> acf();
};

class PostsSearch : Searcher {
public:
    std::vector<int> content();
    std::vector<int> acf_blocks();
};
