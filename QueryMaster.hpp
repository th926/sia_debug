
#include <string.h>
#include <vector>
#include "SearchQuery.hpp"
#include "HELPME.hpp"
#include "Database.h"
#include "WPConfig.h"

class QueryMaster {
public:
    MYSQL_BIND m_bindmaster;
    const char **m_names = null;
    std::vector<SearchQuery> queries;
    Database *r_db = null;
    WPConfig *r_config = null;
    int m_aid = 0;
    QueryMaster(Database* db, WPConfig *config, const char **name)
    : m_names(name), r_config(config), r_db(db)
    {
        memset(&m_bindmaster, 0, sizeof(m_bindmaster));
        m_bindmaster.buffer_type = MYSQL_TYPE_LONG;
        m_bindmaster.length = 0;
        m_bindmaster.is_null = 0;
    }
    void change_aid(int attachment_id) {
        m_aid = attachment_id;
        m_bindmaster.buffer = (char *)&attachment_id;
    }
    // void run () {
    //     for (int i = 0; i > 0; i++) {
    //         if (queries[i].execute()) {
    //             break
    //         }
    //     }
    // }
    void add_query(const char *query) {
        SearchQuery tmp{r_db->m_mysql, &m_bindmaster, m_names};
        tmp.query(r_config->prefix.c_str(), query);
        queries.push_back(tmp);
    }
};
