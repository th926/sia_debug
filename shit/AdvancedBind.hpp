#pragma once
#include <vector>
#include <mysql.h>
#include "HELPME.hpp"
class AdvancedBind {
public:
    std::vector<MYSQL_BIND> binds;
    const char* current_aid = null;
    const char* previous_aid = null;
    int amount = 0;
    void reserve_bind() {
        MYSQL_BIND tmp;
        memset(&tmp, 0, sizeof(MYSQL_BIND));
        binds.push_back(tmp);
        amount++;
    }
    void add_bind(const char*param, unsigned long len, enum enum_field_types type, const char*in_first, const char*in_last) {
        MYSQL_BIND tmp;
        memset(&tmp, 0, sizeof(MYSQL_BIND));
        tmp.buffer = &param;
        tmp.length = &len;
        tmp.buffer_type = type;
        tmp.is_null = 0;
        binds.push_back(tmp);
        amount++;
    }
    void change_aid(const char* aid, unsigned long size) {
        previous_aid = current_aid;
        current_aid = aid;
        for (int i = 0; i < amount; i++) {
            if (binds[i].buffer_type == MYSQL_TYPE_STRING) {
                binds[i].buffer = &current_aid;
                binds[i].length = &size;
                continue;
            }
        }
    }
};
