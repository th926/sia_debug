#pragma once
#include <mysql.h>
#include "WPConfig.h"


class Database {
public:
    MYSQL *m_mysql = nullptr;
    Database(WPConfig &config);
    ~Database();
};
