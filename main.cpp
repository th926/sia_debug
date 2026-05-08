#include "mysqlx/devapi/result.h"
#include <mysqlx/xdevapi.h>
#include <iostream>

// DB_NAME
// DB_USER
// DB_PASSWORD
// DB_HOST
// $table_prefix
//
// prefix
//
// tables
// --------
// posts
// postmeta
// options
//

class wpdb {
    public: std::string posts;
    public: std::string postmeta;
    public: std::string options;
    public: std::string prefix;

    wpdb(std::string in_prefix) {
        prefix = in_prefix;
        posts = prefix.append("posts");
        postmeta = prefix.append("postmeta");
        options = prefix.append("options");
    }
    ~wpdb() {}
};

inline std::vector<int> flatten(mysqlx::RowResult &rowres) {
    std::vector<int> retvec;

    mysqlx::Row row;
    while ((row = rowres.fetchOne()))
    {
      retvec.push_back((int)row[0]);
    }
    return retvec;
}

class Searcher {
protected:
    mysqlx::Table &table;
    int attachment_id;
public:
    Searcher(mysqlx::Table &table, int attachment_id)
        : table(table), attachment_id(attachment_id) {}
    inline std::string caiq(std::string first = "%", std::string last = "%") {
        return first + std::to_string(attachment_id) + last;
    }
};

class OptionSearch : Searcher {
public:
    std::vector<int> widgets() {
        mysqlx::RowResult res = table.select("option_id").where("option_name LIKE :w AND option_value LIKE :a_id").bind("w", "widget_%%").bind("a_id", caiq()).execute();
        return flatten(res);
    }
    std::vector<int> customizer_usage() {
        mysqlx::RowResult res = table.select("option_id").where("option_name LIKE :tms AND option_value LIKE :a_id").bind("tms", "theme_mods_%%").bind("a_id", caiq()).execute();
        return flatten(res);
    }
    std::vector<int> options() {
        mysqlx::RowResult res = table.select("option_id").where("option_value LIKE :a_id").bind("a_id", caiq()).execute();
        return flatten(res);
    }
};

class PostmetaSearch : Searcher {
public:
    std::vector<int> featured_image() {
        mysqlx::RowResult res = table.select("post_id").where("meta_key = :t_id AND meta_value = :a_id").bind("t_id", "_thumbnail_id").bind("a_id", attachment_id).execute();
        return flatten(res);
    }
    std::vector<int> acf() {
        mysqlx::RowResult res = table.select("post_id").where("meta_value = :first OR meta_value = :second OR meta_value = :third and meta_key NOT LIKE :fourth")
            .bind("first", caiq("%\"", "\"%"))
            .bind("second", caiq("%i:"))
            .bind("third", caiq("%attachment_id\";i:", ""))
            .bind("fourth", "_%%")
            .execute();
        return flatten(res);
    }
};

class PostsSearch : Searcher {
public:
    std::vector<int> content() {
        mysqlx::RowResult res = table.select("ID").where("post_status = publish AND (post_content LIKE :first OR post_content LIKE :second OR post_content LIKE :third")
        .bind("first", caiq("%wp-image-"))
        .bind("second", caiq("%attachmend_id=\"", "\"%"))
        .bind("third", caiq("%data-id=\"", "\"%"))
        .execute();
        return flatten(res);
    }
    std::vector<int> acf_blocks() {
        mysqlx::RowResult res = table.select("ID").where("post_type != revision and post_type != attachment AND post_content LIKE :a_id")
        .bind("a_id", caiq("%\":", ",%"))
        .execute();
        return flatten(res);
    }
};

int main(void) {
    // // loot wp config.
    try {
        mysqlx::Session sess("127.0.0.1", 33060, "root", "123");
        mysqlx::Schema db = sess.getSchema("absmnew"); // Needs to be figured out from wp_config.php
        mysqlx::Table table = db.getTable("wp_posts");
    } catch (const mysqlx::Error &err) {
        std::cerr << "MySQL Error: " << err.what() << std::endl;
    }
    return 0;
}

// Options
// ---
// x widget search
// x customizer usage
// x options search
//
// Postmeta
// ---
// acf search
// featured image search
//
// Posts
// ---
// content search
// acf block search
