import os, subprocess

def file_exists(filename):
    return os.path.exists(filename)

def kill_quit(filename, command='kill -s QUIT {0}'):
    if os.path.exists(filename):
        pid = int(open(filename).read())
        return subprocess.check_output(command.format(pid), shell=True)

if __name__ == '__main__':
    kill_quit('test/out/nginx.pid', 'sudo kill -s QUIT {0}')
    kill_quit('test/out/fpm.pid')